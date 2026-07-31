<?php

namespace App\Http\Controllers;

use App\Models\TrainingRun;
use App\Support\CurrentPlayer;
use App\Support\WordList;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function show(Request $request)
    {
        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

        if (! $player->name) {
            return CurrentPlayer::attach(redirect('/player'), $player);
        }

        $conf = config('training');
        $pc = $player->currentStats();

        $weak = collect($player->weakKana($conf['target_kana']))->pluck('kana')->all();
        $words = WordList::forKana($weak, $conf['word_count'], $conf['word_sets'] ?? null);

        $run = TrainingRun::create([
            'player_id' => $player->id,
            'character' => $pc->character,
            'target_kana' => $weak,
            'word_list' => $words,
            'level_before' => $pc->level,
        ]);

        return CurrentPlayer::attach(
            response()->view('training', [
                'player' => $player,
                'conf' => $conf,
                'me' => $pc->stats(),
                'level' => $pc->level,
                'weak' => $weak,
                'words' => $words,
                'runId' => $run->id,
            ]),
            $player
        );
    }

    public function finish(Request $request)
    {
        $data = $request->validate([
            'run_id' => ['required', 'integer'],
            'words' => ['required', 'integer', 'min:0', 'max:9999'],
            'typed_chars' => ['required', 'integer', 'min:0', 'max:999999'],
            'miss_count' => ['required', 'integer', 'min:0', 'max:999999'],
            'max_combo' => ['required', 'integer', 'min:0', 'max:99999'],
            'duration_ms' => ['required', 'integer', 'min:0', 'max:99999999'],
            'miss_map' => ['nullable', 'array'],
        ]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $run = TrainingRun::where('id', $data['run_id'])->where('player_id', $player->id)->first();

        if (! $run) {
            return response()->json(['ok' => false], 404);
        }

        if ($run->finished) {
            return response()->json(['ok' => true, 'redirect' => "/training/result/{$run->id}"]);
        }

        $conf = config('training');
        $stats = $player->statsFor($run->character);

        $listCount = count($run->word_list ?? []);
        $words = min($data['words'], $listCount);

        $elapsed = min($data['duration_ms'], ($conf['time_limit'] + 5) * 1000);
        $typed = min($data['typed_chars'], $elapsed > 0 ? (int) ceil($elapsed / 1000 * 20) : 0);

        while ($words > 0 && $typed < $run->minCharsFor($words)) {
            $words--;
        }

        $weak = $run->weakCountFor($words);
        $normal = max(0, $words - $weak);

        $misses = min($data['miss_count'], $typed + 500);
        $total = $typed + $misses;
        $accuracy = $total > 0 ? $typed / $total * 100 : 0;

        $gain = $normal * $conf['exp_per_word']
            + $weak * $conf['exp_per_weak']
            + ($words >= $listCount && $accuracy >= $conf['accuracy_line'] ? $conf['accuracy_bonus'] : 0);

        $levelBefore = $stats->level;
        $stats->addExp($gain);

        $run->update([
            'words' => $words,
            'weak_words' => $weak,
            'typed_chars' => $typed,
            'miss_count' => $misses,
            'max_combo' => min($data['max_combo'], $words),
            'duration_ms' => $elapsed,
            'miss_map' => $data['miss_map'] ?? null,
            'exp_gained' => $gain,
            'level_before' => $levelBefore,
            'level_after' => $stats->level,
            'finished' => true,
        ]);

        if (! empty($data['miss_map'])) {
            $player->recordMisses($data['miss_map']);
        }

        return response()->json(['ok' => true, 'redirect' => "/training/result/{$run->id}"]);
    }

    public function result(Request $request, int $run)
    {
        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $r = TrainingRun::where('id', $run)->where('player_id', $player->id)->first();

        if (! $r || ! $r->finished) {
            return redirect('/character');
        }

        $conf = config('training');

        return CurrentPlayer::attach(
            response()->view('training-result', [
                'run' => $r,
                'conf' => $conf,
                'pc' => $player->statsFor($r->character),
                'charConf' => config('characters.' . $r->character),
                'cleared' => $r->accuracy() >= $conf['accuracy_line'],
                'missTop' => collect($r->miss_map ?? [])->sortDesc()->take(4)->all(),
            ]),
            $player
        );
    }
}