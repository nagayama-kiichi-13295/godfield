<?php

namespace App\Http\Controllers;

use App\Support\CurrentPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

        if (! $player->name) {
            return CurrentPlayer::attach(redirect('/player'), $player);
        }

        $stages = config('stages');
        $chars = config('characters');
        $pid = $player->id;

        $accExpr = 'MAX(CASE WHEN typed_chars + miss_count > 0
            THEN typed_chars * 100.0 / (typed_chars + miss_count) ELSE 0 END)';

        $kpsExpr = 'MAX(CASE WHEN duration_ms > 0
            THEN typed_chars * 1000.0 / duration_ms ELSE 0 END)';

        $agg = fn (string $table) => DB::table($table)
            ->where('player_id', $pid)
            ->where('finished', true)
            ->selectRaw('COUNT(*) as plays')
            ->selectRaw('COALESCE(SUM(exp_gained), 0) as exp')
            ->selectRaw('COALESCE(SUM(duration_ms), 0) as ms')
            ->selectRaw('COALESCE(MAX(max_combo), 0) as combo')
            ->selectRaw("COALESCE({$accExpr}, 0) as acc")
            ->selectRaw("COALESCE({$kpsExpr}, 0) as kps")
            ->first();

        $solo = $agg('solo_runs');
        $train = $agg('training_runs');
        $endless = $agg('endless_runs');

        $bestTyping = [
            'kps' => round(max($solo->kps, $train->kps, $endless->kps), 2),
            'combo' => (int) max($solo->combo, $train->combo, $endless->combo),
            'accuracy' => round(max($solo->acc, $train->acc, $endless->acc), 1),
        ];

        $best = DB::table('solo_runs')
            ->where('player_id', $pid)
            ->select('stage', DB::raw('MAX(cleared) as best'), DB::raw('COUNT(*) as plays'))
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $rows = [];

        foreach ($stages as $key => $st) {
            $total = count($st['enemies']) + (empty($st['boss']) ? 0 : 1);
            $r = $best->get($key);

            $rows[] = [
                'label' => $st['label'],
                'color' => $st['color'],
                'total' => $total,
                'best' => (int) ($r->best ?? 0),
                'plays' => (int) ($r->plays ?? 0),
                'cleared' => (int) ($r->best ?? 0) >= $total,
            ];
        }

        $charStats = [];

        foreach ($chars as $key => $c) {
            $pc = $player->statsFor($key);

            $charStats[] = [
                'name' => $c['name'],
                'color' => $c['color'],
                'icon' => $c['icon'],
                'level' => $pc->level,
                'wins' => $pc->wins,
                'losses' => $pc->losses,
                'stats' => $pc->stats(),
            ];
        }

        $trainSum = DB::table('training_runs')
            ->where('player_id', $pid)
            ->where('finished', true)
            ->selectRaw('COALESCE(SUM(words), 0) as words')
            ->selectRaw('COALESCE(SUM(weak_words), 0) as weak_words')
            ->first();

        $trend = DB::table('training_runs')
            ->where('player_id', $pid)
            ->where('finished', true)
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'typed_chars', 'miss_count', 'duration_ms', 'created_at'])
            ->reverse()
            ->values()
            ->map(function ($r) {
                $all = $r->typed_chars + $r->miss_count;

                return [
                    'accuracy' => $all > 0 ? round($r->typed_chars / $all * 100, 1) : 0.0,
                    'kps' => $r->duration_ms > 0 ? round($r->typed_chars / ($r->duration_ms / 1000), 2) : 0.0,
                    'date' => Carbon::parse($r->created_at)->format('m/d'),
                ];
            })
            ->all();

        $endlessStats = DB::table('endless_runs')
            ->where('player_id', $pid)
            ->where('finished', true)
            ->select('mode')
            ->selectRaw('MAX(defeated) as best')
            ->selectRaw('COUNT(*) as plays')
            ->selectRaw('COALESCE(SUM(defeated), 0) as kills')
            ->selectRaw('COALESCE(SUM(exp_gained), 0) as exp')
            ->groupBy('mode')
            ->get()
            ->keyBy('mode');

        $endlessRecent = DB::table('endless_runs')
            ->where('player_id', $pid)
            ->where('finished', true)
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'mode', 'defeated', 'exp_gained', 'typed_chars', 'miss_count', 'created_at'])
            ->groupBy('mode');

        $endlessCards = [];

        foreach (config('endless') as $key => $conf) {
            $g = $endlessStats->get($key);

            $endlessCards[$key] = [
                'label' => $conf['label'],
                'color' => $conf['color'],
                'unit' => $conf['mode'] === 'depth' ? '階' : '体',
                'best' => (int) ($g->best ?? 0),
                'plays' => (int) ($g->plays ?? 0),
                'total_kills' => (int) ($g->kills ?? 0),
                'exp' => (int) ($g->exp ?? 0),
                'recent' => collect($endlessRecent->get($key, []))
                    ->take(5)
                    ->map(function ($r) {
                        $all = $r->typed_chars + $r->miss_count;

                        return [
                            'id' => $r->id,
                            'defeated' => $r->defeated,
                            'accuracy' => $all > 0 ? round($r->typed_chars / $all * 100, 1) : 0.0,
                            'exp' => $r->exp_gained,
                            'date' => Carbon::parse($r->created_at)->format('m/d H:i'),
                        ];
                    })
                    ->all(),
            ];
        }

        return CurrentPlayer::attach(
            response()->view('record', [
                'player' => $player,
                'rows' => $rows,
                'charStats' => $charStats,
                'totalRuns' => (int) DB::table('solo_runs')->where('player_id', $pid)->count(),
                'totalExp' => (int) DB::table('solo_runs')->where('player_id', $pid)->sum('exp_gained')
                    + (int) $train->exp + (int) $endless->exp,
                'recent' => $player->runs()->latest('id')->take(8)->get(),
                'trainRecent' => $player->trainings()->where('finished', true)->latest('id')->take(8)->get(),
                'trainCount' => (int) $train->plays,
                'trainWords' => (int) $trainSum->words,
                'trainWeakWords' => (int) $trainSum->weak_words,
                'trend' => $trend,
                'playMinutes' => (int) round(($solo->ms + $train->ms + $endless->ms) / 60000),
                'weak' => $player->weakKana(6),
                'bestTyping' => $bestTyping,
                'endless' => $endlessCards,
                'endlessCount' => (int) $endless->plays,
                'stages' => $stages,
                'chars' => $chars,
            ]),
            $player
        );
    }
}