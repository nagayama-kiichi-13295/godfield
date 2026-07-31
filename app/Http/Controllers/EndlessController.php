<?php

namespace App\Http\Controllers;

use App\Models\EndlessRun;
use App\Support\CurrentPlayer;
use App\Support\EndlessGen;
use App\Support\WordList;
use Illuminate\Http\Request;

class EndlessController extends Controller
{
    public function show(Request $request, string $mode)
    {
        $conf = config('endless.' . $mode);

        if (! $conf) {
            return redirect('/character');
        }

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

        if (! $player->name) {
            return CurrentPlayer::attach(redirect('/player'), $player);
        }

        $pc = $player->currentStats();

        $run = EndlessRun::create([
            'player_id' => $player->id,
            'character' => $pc->character,
            'mode' => $mode,
            'level_before' => $pc->level,
        ]);

        return CurrentPlayer::attach(
            response()->view('endless', [
                'player' => $player,
                'mode' => $mode,
                'conf' => $conf,
                'me' => $pc->stats(),
                'level' => $pc->level,
                'enemies' => EndlessGen::batch($mode, 0, 60),
                'words' => WordList::random(80, $conf['word_sets'] ?? null),
                'runId' => $run->id,
                'best' => $player->endlessBest($mode),
            ]),
            $player
        );
    }

    public function finish(Request $request)
    {
        $data = $request->validate([
            'run_id' => ['required', 'integer'],
            'defeated' => ['required', 'integer', 'min:0', 'max:9999'],
            'max_combo' => ['required', 'integer', 'min:0', 'max:99999'],
            'typed_chars' => ['required', 'integer', 'min:0', 'max:999999'],
            'miss_count' => ['required', 'integer', 'min:0', 'max:999999'],
            'duration_ms' => ['required', 'integer', 'min:0', 'max:99999999'],
            'miss_map' => ['nullable', 'array'],
        ]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $run = EndlessRun::where('id', $data['run_id'])->where('player_id', $player->id)->first();

        if (! $run) {
            return response()->json(['ok' => false], 404);
        }

        if ($run->finished) {
            return response()->json(['ok' => true, 'redirect' => "/endless/result/{$run->id}"]);
        }

        $stats = $player->statsFor($run->character);
        $levelBefore = $stats->level;

        $gain = EndlessGen::totalExp($run->mode, $data['defeated']);
        $stats->addExp($gain);

        if ($data['defeated'] > 0) {
            $stats->increment('wins');
        } else {
            $stats->increment('losses');
        }

        $run->update([
            'defeated' => $data['defeated'],
            'exp_gained' => $gain,
            'level_before' => $levelBefore,
            'level_after' => $stats->level,
            'max_combo' => $data['max_combo'],
            'typed_chars' => $data['typed_chars'],
            'miss_count' => $data['miss_count'],
            'duration_ms' => $data['duration_ms'],
            'miss_map' => $data['miss_map'] ?? null,
            'finished' => true,
        ]);

        if (! empty($data['miss_map'])) {
            $player->recordMisses($data['miss_map']);
        }

        return response()->json(['ok' => true, 'redirect' => "/endless/result/{$run->id}"]);
    }

    public function result(Request $request, int $run)
    {
        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $r = EndlessRun::where('id', $run)->where('player_id', $player->id)->first();

        if (! $r || ! $r->finished) {
            return redirect('/character');
        }

        $prevBest = (int) $player->endlessRuns()
            ->where('mode', $r->mode)
            ->where('id', '<', $r->id)
            ->max('defeated');

        return CurrentPlayer::attach(
            response()->view('endless-result', [
                'run' => $r,
                'conf' => $r->config(),
                'pc' => $player->statsFor($r->character),
                'charConf' => config('characters.' . $r->character),
                'prevBest' => $prevBest,
                'isNewBest' => $r->defeated > $prevBest,
                'missTop' => collect($r->miss_map ?? [])->sortDesc()->take(4)->all(),
            ]),
            $player
        );
    }
}