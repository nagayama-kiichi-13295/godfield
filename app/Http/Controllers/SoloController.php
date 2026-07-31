<?php

namespace App\Http\Controllers;

use App\Models\SoloRun;
use App\Support\CurrentPlayer;
use App\Support\WordList;
use Illuminate\Http\Request;

class SoloController extends Controller
{
    public function show(Request $request, string $stage = 'training')
    {
        $conf = config('stages.' . $stage);

        if (! $conf) {
            return redirect('/character');
        }

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $pc = $player->currentStats();

        $run = SoloRun::create([
            'player_id' => $player->id,
            'character' => $pc->character,
            'stage' => $stage,
        ]);

        return CurrentPlayer::attach(
            response()->view('solo', [
                'player' => $player,
                'me' => $pc->stats(),
                'level' => $pc->level,
                'stage' => $stage,
                'stageConf' => $conf,
                'boss' => empty($conf['boss']) ? null : config('bosses.' . $conf['boss']),
                'runId' => $run->id,
                'words' => WordList::random(60, $conf['word_sets'] ?? null),
                'defenseWords' => WordList::random(20, ['short']),
            ]),
            $player
        );
    }

    public function defeat(Request $request)
    {
        $data = $request->validate([
            'run_id' => ['required', 'integer'],
            'index' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $run = SoloRun::where('id', $data['run_id'])->where('player_id', $player->id)->first();

        if (! $run || $run->finished || $data['index'] !== $run->cleared) {
            return response()->json(['ok' => false], 422);
        }

        $conf = $run->stageConfig();
        $enemies = $conf['enemies'];
        $idx = $data['index'];

        if ($idx < count($enemies)) {
            $gain = (int) $enemies[$idx]['exp'];
        } elseif ($idx === count($enemies) && ! empty($conf['boss'])) {
            $gain = (int) config('bosses.' . $conf['boss'] . '.exp', 0);
        } else {
            return response()->json(['ok' => false], 422);
        }

        $stats = $player->statsFor($run->character);
        $leveled = $stats->addExp($gain);

        $run->increment('cleared');
        $run->increment('exp_gained', $gain);

        return response()->json([
            'gain' => $gain,
            'level' => $stats->level,
            'exp' => $stats->exp,
            'required' => $stats->requiredExp(),
            'leveled' => $leveled,
        ]);
    }

    public function finish(Request $request)
    {
        $data = $request->validate([
            'run_id' => ['required', 'integer'],
            'max_combo' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'typed_chars' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'miss_count' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'miss_map' => ['nullable', 'array'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:99999999'],
        ]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $run = SoloRun::where('id', $data['run_id'])->where('player_id', $player->id)->first();

        if (! $run) {
            return response()->json(['ok' => false], 404);
        }

        if ($run->finished) {
            return response()->json(['ok' => true, 'redirect' => "/solo/result/{$run->id}"]);
        }

        $conf = $run->stageConfig();
        $stats = $player->statsFor($run->character);

        $levelBefore = $stats->level;
        $cleared = $run->cleared >= $run->totalEnemies();
        $bonus = 0;
        $drop = null;

        if ($cleared) {
            $bonus = (int) $conf['clear_bonus'];
            $stats->addExp($bonus);
            $stats->increment('wins');
            $run->increment('exp_gained', $bonus);

            $got = $player->grantFrom($conf['drops'] ?? []);
            $drop = $got['item'] ?? null;
        } else {
            $stats->increment('losses');
        }

        $run->update([
            'finished' => true,
            'is_cleared' => $cleared,
            'level_before' => $levelBefore,
            'level_after' => $stats->level,
            'bonus_exp' => $bonus,
            'drop_item' => $drop,
            'max_combo' => $data['max_combo'] ?? 0,
            'typed_chars' => $data['typed_chars'] ?? 0,
            'miss_count' => $data['miss_count'] ?? 0,
            'miss_map' => $data['miss_map'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? 0,
        ]);

        if (! empty($data['miss_map'])) {
            $player->recordMisses($data['miss_map']);
        }

        return response()->json(['ok' => true, 'redirect' => "/solo/result/{$run->id}"]);
    }

    public function result(Request $request, int $run)
    {
        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $r = SoloRun::where('id', $run)->where('player_id', $player->id)->first();

        if (! $r || ! $r->finished) {
            return redirect('/character');
        }

        $pc = $player->statsFor($r->character);

        $prev = SoloRun::where('player_id', $player->id)
            ->where('finished', true)
            ->where('id', '<', $r->id)
            ->where('typed_chars', '>', 0)
            ->get();

        $records = [];

        if ($r->typed_chars > 0) {
            $records = [
                'kps' => $prev->every(fn ($p) => $r->kps() > $p->kps()),
                'combo' => $prev->every(fn ($p) => $r->max_combo > $p->max_combo),
                'reach' => $prev->where('stage', $r->stage)->every(fn ($p) => $r->cleared > $p->cleared),
            ];
        }

        return CurrentPlayer::attach(
            response()->view('result', [
                'run' => $r,
                'stageConf' => $r->stageConfig(),
                'charConf' => config('characters.' . $r->character),
                'pc' => $pc,
                'records' => $records,
                'dropName' => $r->drop_item ? config('equipment.items.' . $r->drop_item . '.name') : null,
                'dropColor' => $r->drop_item ? config('equipment.items.' . $r->drop_item . '.color') : null,
                'missTop' => collect($r->miss_map ?? [])->sortDesc()->take(4)->all(),
            ]),
            $player
        );
    }
}