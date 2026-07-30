<?php

use App\Events\MatchFinished;
use App\Events\MatchFound;
use App\Events\MatchStarted;
use App\Events\PlayerProgressed;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\SoloRun;
use App\Models\TrainingRun;
use App\Models\EndlessRun;
use App\Support\EndlessGen;
use App\Support\CurrentPlayer;
use App\Support\Stats;
use App\Support\WordList;
use App\Support\Romaji;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', fn() => view('title'));

Route::get('/player', fn() => view('player'));

Route::post('/player', function (Request $request) {
    $request->validate(['name' => ['required', 'string', 'max:20']]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $player->update(['name' => $request->name]);

    return CurrentPlayer::attach(redirect('/character'), $player);
});

Route::get('/character', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (! $player->name) {
        return CurrentPlayer::attach(redirect('/player'), $player);
    }

    $stats = [];
    foreach (array_keys(config('characters')) as $key) {
        $stats[$key] = $player->statsFor($key);
    }

    return CurrentPlayer::attach(
        response()->view('character', [
            'player' => $player,
            'characters' => config('characters'),
            'stats' => $stats,
            'stages' => config('stages'),
            'growth' => config('growth'),
            'equipment' => config('equipment'),
            'owned' => $player->ownedItems(),
        ]),
        $player
    );
});

Route::post('/character', function (Request $request) {
    $data = $request->validate(['character' => ['required', 'string']]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (array_key_exists($data['character'], config('characters'))) {
        $player->update(['character' => $data['character']]);
        $player->statsFor($data['character']);
    }

    return CurrentPlayer::attach(redirect('/character'), $player);
});

Route::post('/character/equip', function (Request $request) {
    $data = $request->validate([
        'character' => ['required', 'string'],
        'slot' => ['required', 'string'],
        'item' => ['nullable', 'string'],
    ]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (! array_key_exists($data['character'], config('characters'))) {
        return CurrentPlayer::attach(redirect('/character'), $player);
    }

    $item = $data['item'] ?: null;

    if ($item !== null && ! in_array($item, $player->ownedItems(), true)) {
        return CurrentPlayer::attach(redirect('/character'), $player);
    }

    $player->statsFor($data['character'])->equip($data['slot'], $item);

    return CurrentPlayer::attach(redirect('/character'), $player);
});

Route::post('/character/allocate', function (Request $request) {
    $data = $request->validate([
        'character' => ['required', 'string'],
        'stat' => ['required', 'string'],
        'amount' => ['required', 'integer', 'in:1,-1'],
    ]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (array_key_exists($data['character'], config('characters'))) {
        $player->statsFor($data['character'])->allocate($data['stat'], $data['amount']);
    }

    return CurrentPlayer::attach(redirect('/character'), $player);
});

Route::post('/character/reset', function (Request $request) {
    $data = $request->validate(['character' => ['required', 'string']]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (array_key_exists($data['character'], config('characters'))) {
        $player->statsFor($data['character'])->resetPoints();
    }

    return CurrentPlayer::attach(redirect('/character'), $player);
});

Route::get('/matching', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (! $player->name) {
        return CurrentPlayer::attach(redirect('/player'), $player);
    }

    return CurrentPlayer::attach(
        response()->view('matching', ['name' => $player->name]),
        $player
    );
});

Route::post('/matching/join', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (! $player->name) {
        return response()->json(['error' => 'no_name'], 422);
    }

    $stats = $player->currentStats();

    [$match, $role] = DB::transaction(function () use ($player, $stats) {
        $waiting = GameMatch::where('status', 'waiting')
            ->whereNull('player2_id')
            ->where('player1_id', '!=', $player->id)
            ->where('created_at', '>', now()->subMinutes(2))
            ->lockForUpdate()
            ->oldest()
            ->first();

        if ($waiting) {
            $waiting->update([
                'player2_id' => $player->id,
                'player2' => $player->name,
                'player2_char' => $stats->character,
                'player2_level' => $stats->level,
                'player2_stas' => $stats->stats(),
                'status' => 'playing',
            ]);

            return [$waiting, 'player2'];
        }

        GameMatch::where('player1_id', $player->id)
            ->where('status', 'waiting')
            ->delete();

        return [GameMatch::create([
            'player1_id' => $player->id,
            'player1' => $player->name,
            'player1_char' => $stats->character,
            'player1_level' => $stats->level,
            'player1_stats' => $stats->stats(),
            'status' => 'waiting',
        ]), 'player1'];
    });

    if ($role === 'player2') {
        broadcast(new MatchFound($match->id, $match->player1, $match->player2));
    }

    return CurrentPlayer::attach(
        response()->json([
            'match_id' => $match->id,
            'status' => $match->status,
            'role' => $role,
        ]),
        $player
    );
});

Route::get('/matching/status', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    $match = GameMatch::where(function ($q) use ($player) {
        $q->where('player1_id', $player->id)->orWhere('player2_id', $player->id);
    })->latest('id')->first();

    if (! $match) {
        return response()->json(['status' => 'none']);
    }

    return response()->json(['status' => $match->status, 'match_id' => $match->id]);
});

Route::post('/matching/cancel', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    GameMatch::where('player1_id', $player->id)->where('status', 'waiting')->delete();

    return response()->noContent();
});

Route::get('/match/{matchId}', function (Request $request, string $matchId) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $match = GameMatch::find($matchId);

    if (! $match || ! in_array($player->id, [$match->player1_id, $match->player2_id], true)) {
        return redirect('/character');
    }

    $isP1 = $match->player1_id === $player->id;

    $me = ($isP1 ? $match->player1_stats : $match->player2_stats)
        ?? Stats::of(
            $isP1 ? $match->player1_char : $match->player2_char,
            $isP1 ? $match->player1_level : $match->player2_level
        );

    $opp = ($isP1 ? $match->player2_stats : $match->player1_stats)
        ?? Stats::of(
            $isP1 ? $match->player2_char : $match->player1_char,
            $isP1 ? $match->player2_level : $match->player1_level
        );

    return CurrentPlayer::attach(
        response()->view('match', [
            'matchId' => $matchId,
            'words' => WordList::forMatch($matchId, 60, ['short', 'long']),
            'meName' => $isP1 ? $match->player1 : $match->player2,
            'oppName' => $isP1 ? $match->player2 : $match->player1,
            'meLevel' => $isP1 ? $match->player1_level : $match->player2_level,
            'oppLevel' => $isP1 ? $match->player2_level : $match->player1_level,
            'me' => $me,
            'opp' => $opp,
        ]),
        $player
    );
});

Route::post('/match/{matchId}/start', function (string $matchId) {
    $startAt = (int) (microtime(true) * 1000) + 4000;

    broadcast(new MatchStarted($matchId, $startAt));

    return response()->json(['start_at' => $startAt]);
});

Route::post('/match/{matchId}/progress', function (Request $request, string $matchId) {
    $data = $request->validate([
        'player_key' => ['required', 'string', 'max:64'],
        'word_index' => ['required', 'integer', 'min:0', 'max:9999'],
        'damage' => ['required', 'integer', 'min:0', 'max:99999'],
        'combo' => ['required', 'integer', 'min:0', 'max:9999'],
        'hp' => ['required', 'integer', 'min:0', 'max:99999'],
        'healed' => ['required', 'integer', 'min:0', 'max:99999'],
    ]);

    broadcast(new PlayerProgressed(
        $matchId,
        $data['player_key'],
        $data['word_index'],
        $data['damage'],
        $data['combo'],
        $data['hp'],
        $data['healed'],
    ))->toOthers();

    return response()->noContent();
});

Route::post('/match/{matchId}/finish', function (Request $request, string $matchId) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $match = GameMatch::find($matchId);

    if (! $match || ! in_array($player->id, [$match->player1_id, $match->player2_id], true)) {
        return response()->json(['error' => 'forbidden'], 403);
    }

    DB::transaction(function () use ($match, $player) {
        $fresh = GameMatch::whereKey($match->id)->lockForUpdate()->first();

        if ($fresh->status === 'finished') {
            return;
        }

        $winnerIsP1 = $fresh->player1_id === $player->id;

        $fresh->update([
            'status' => 'finished',
            'winner_id' => $player->id,
            'winner' => $winnerIsP1 ? $fresh->player1 : $fresh->player2,
        ]);

        $winner = Player::find($player->id);
        $loser = Player::find($winnerIsP1 ? $fresh->player2_id : $fresh->player1_id);

        if ($winner) {
            $s = $winner->statsFor($winnerIsP1 ? $fresh->player1_char : $fresh->player2_char);
            $s->addExp(config('battle.online_exp_win'));
            $s->increment('wins');
        }

        if ($loser) {
            $s = $loser->statsFor($winnerIsP1 ? $fresh->player2_char : $fresh->player1_char);
            $s->addExp(config('battle.online_exp_lose'));
            $s->increment('losses');
        }

        broadcast(new MatchFinished($match->id, $fresh->winner ?? '不明'));
    });

    return response()->json(['ok' => true]);
});

Route::get('/match/{matchId}/result', function (Request $request, string $matchId) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $match = GameMatch::find($matchId);

    if (! $match || $match->status !== 'finished') {
        return response()->json(['ready' => false]);
    }

    $isP1 = $match->player1_id === $player->id;
    $stats = $player->statsFor($isP1 ? $match->player1_char : $match->player2_char);
    $won = $match->winner_id === $player->id;

    return response()->json([
        'ready' => true,
        'won' => $won,
        'winner' => $match->winner,
        'character' => $stats->name(),
        'gain' => $won ? config('battle.online_exp_win') : config('battle.online_exp_lose'),
        'level' => $stats->level,
        'exp' => $stats->exp,
        'required' => $stats->requiredExp(),
    ]);
});

Route::get('/solo/{stage?}', function (Request $request, string $stage = 'training') {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $pc = $player->currentStats();
    $conf = config('stages.' . $stage);

    if (! $conf) {
        return redirect('/character');
    }

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
            'runId' => $run->id,
            'words' => WordList::random(60, $conf['word_sets'] ?? null),
            'defenseWords' => WordList::random(20, ['short']),
            'boss' => empty($conf['boss']) ? null : config('bosses.' . $conf['boss']),
        ]),
        $player
    );
});

Route::post('/solo/defeat', function (Request $request) {
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
});

Route::post('/solo/finish', function (Request $request) {
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
        $drop = $player->grantFrom($conf['drops'] ?? []);
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
});

Route::get('/solo/result/{run}', function (Request $request, int $run) {
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
            'dropName' => $r->drop_item ? config('equipment.items.' . $r->drop_item . '.name') : null,
            'dropColor' => $r->drop_item ? config('equipment.items.' . $r->drop_item . '.color') : null,
            'records' => $records,
            'missTop' => collect($r->miss_map ?? [])->sortDesc()->take(4)->all(),
        ]),
        $player
    );
});

Route::get('/record', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    if (! $player->name) {
        return CurrentPlayer::attach(redirect('/player'), $player);
    }

    $stages = config('stages');
    $chars = config('characters');

    $best = $player->runs()
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

    $soloRuns = $player->runs()->where('finished', true)->where('typed_chars', '>', 0)->get();
    $trainRuns = $player->trainings()->where('finished', true)->get();
    $endlessRuns = $player->endlessRuns()->where('finished', true)->get();
    $allRuns = $soloRuns->concat($trainRuns)->concat($endlessRuns->filter(fn ($r) => $r->typed_chars > 0));

    $bestTyping = [
        'kps' => round((float) $allRuns->max(fn ($r) => $r->kps()), 2),
        'combo' => (int) $allRuns->max('max_combo'),
        'accuracy' => round((float) $allRuns->max(fn ($r) => $r->accuracy()), 1),
    ];

    $trend = $trainRuns->sortBy('id')->take(-12)->values()->map(fn ($r) => [
        'accuracy' => $r->accuracy(),
        'kps' => $r->kps(),
        'date' => $r->created_at->format('m/d'),
    ])->all();

    $playMs = (int) ($soloRuns->sum('duration_ms') + $trainRuns->sum('duration_ms') + $endlessRuns->sum('duration_ms'));

    return CurrentPlayer::attach(
        response()->view('record', [
            'player' => $player,
            'rows' => $rows,
            'charStats' => $charStats,
            'totalRuns' => $player->runs()->count(),
            'totalExp' => (int) (
                $player->runs()->sum('exp_gained')
                + $trainRuns->sum('exp_gained')
                + $endlessRuns->sum('exp_gained')
            ),
            'endless' => $player->endlessSummary(),
            'endlessCount' => $endlessRuns->count(),
            'recent' => $player->runs()->latest('id')->take(8)->get(),
            'trainRecent' => $player->trainings()->where('finished', true)->latest('id')->take(8)->get(),
            'trainCount' => $trainRuns->count(),
            'trainWords' => (int) $trainRuns->sum('words'),
            'trainWeakWords' => (int) $trainRuns->sum('weak_words'),
            'trend' => $trend,
            'playMinutes' => (int) round($playMs / 60000),
            'weak' => $player->weakKana(6),
            'bestTyping' => $bestTyping,
            'stages' => $stages,
            'chars' => $chars,
        ]),
        $player
    );
});

Route::get('/training', function (Request $request) {
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
});

Route::post('/training/finish', function (Request $request) {
    $data = $request->validate([
        'run_id' => ['required', 'integer'],
        'words' => ['required', 'integer', 'min:0', 'max:9999'],
        'weak_words' => ['required', 'integer', 'min:0', 'max:9999'],
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

    $normal = max(0, $data['words'] - $data['weak_words']);
    $accuracy = $data['typed_chars'] + $data['miss_count'] > 0
        ? $data['typed_chars'] / ($data['typed_chars'] + $data['miss_count']) * 100
        : 0;

    $gain = $normal * $conf['exp_per_word']
        + $data['weak_words'] * $conf['exp_per_weak']
        + ($accuracy >= $conf['accuracy_line'] ? $conf['accuracy_bonus'] : 0);

    $levelBefore = $stats->level;
    $stats->addExp($gain);

    $run->update([
        'words' => $data['words'],
        'weak_words' => $data['weak_words'],
        'typed_chars' => $data['typed_chars'],
        'miss_count' => $data['miss_count'],
        'max_combo' => $data['max_combo'],
        'duration_ms' => $data['duration_ms'],
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
});

Route::get('/training/result/{run}', function (Request $request, int $run) {
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
});

Route::get('/endless/{mode}', function (Request $request, string $mode) {
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
});

Route::post('/endless/finish', function (Request $request) {
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
});

Route::get('/endless/result/{run}', function (Request $request, int $run) {
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
});