<?php

use App\Events\MatchFound;
use App\Events\MatchStarted;
use App\Events\PlayerProgressed;
use App\Events\MatchFinished;
use App\Models\GameMatch;
use App\Support\WordList;
use App\Support\CurrentPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('title'));

Route::get('/player', fn() => view('player'));

Route::post('/player', function (Request $request) {
    $request->validate(['name' => ['required', 'string', 'max:20']]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $player->update(['name' => $request->name]);

    session(['player_name' => $request->name]);

    return CurrentPlayer::attach(redirect('/character'), $player);
});

Route::get('/matching', function () {
    if (! session('player_name')) {
        return redirect('/player');
    }

    return view('matching', ['name' => session('player_name')]);
});

Route::post('/matching/join', function () {
    $name = session('player_name');

    if (! $name) {
        return response()->json(['error' => 'no_name'], 422);
    }

    [$match, $role] = DB::transaction(function () use ($name) {
        $waiting = GameMatch::where('status', 'waiting')
            ->whereNull('player2')
            ->where('created_at', '>', now()->subMinute())
            ->lockForUpdate()
            ->oldest()
            ->first();

        if ($waiting) {
            $waiting->update(['player2' => $name, 'status' => 'playing']);

            return [$waiting, 'player2'];
        }

        return [GameMatch::create(['player1' => $name, 'status' => 'waiting']), 'player1'];
    });

    session(['match_id' => $match->id, 'match_role' => $role]);

    if ($role === 'player2') {
        broadcast(new MatchFound($match->id, $match->player1, $match->player2));
    }

    return response()->json([
        'match_id' => $match->id,
        'status' => $match->status,
        'role' => $role,
    ]);
});

Route::get('/matching/status', function () {
    $id = session('match_id');
    $match = $id ? GameMatch::find($id) : null;

    if (! $match) {
        return response()->json(['status' => 'none']);
    }

    return response()->json([
        'status' => $match->status,
        'match_id' => $match->id,
    ]);
});

Route::post('/matching/cancel', function () {
    $id = session('match_id');

    if ($id) {
        GameMatch::where('id', $id)->where('status', 'waiting')->delete();
        session()->forget(['match_id', 'match_role']);
    }

    return response()->noContent();
});

Route::get('/match/{matchId}', function (string $matchId) {
    $match = GameMatch::find($matchId);
    $role = session('match_role');

    $me = 'あなた';
    $opponent = '相手';

    if ($match) {
        $me = ($role === 'player2' ? $match->player2 : $match->player1) ?? 'あなた';
        $opponent = ($role === 'player2' ? $match->player1 : $match->player2) ?? '相手';
    }

    return view('match', [
        'matchId' => $matchId,
        'words' => WordList::forMatch($matchId),
        'me' => $me,
        'opponent' => $opponent,
    ]);
});

Route::post('/match/{matchId}/start', function (string $matchId) {
    $startAt = (int) (microtime(true) * 1000) + 4000;

    broadcast(new MatchStarted($matchId, $startAt));

    return response()->json(['start_at' => $startAt]);
});

Route::post('/match/{matchId}/progress', function (Request $request, string $matchId) {
    $data = $request->validate([
        'player_key' => ['required', 'string', 'max:64'],
        'word_index' => ['required', 'integer', 'min:0', 'max:999'],
    ]);

    broadcast(new PlayerProgressed($matchId, $data['player_key'], $data['word_index']))
        ->toOthers();

    return response()->noContent();
});

Route::post('/match/{matchId}/finish', function (string $matchId) {
    $match = GameMatch::find($matchId);

    if (! $match) {
        return response()->json(['winner' => null], 404);
    }

    if ($match->status === 'finished') {
        return response()->json(['winner' => $match->winner]);
    }

    $winner = session('match_role') === 'player2' ? $match->player2 : $match->player1;

    $match->update(['status' => 'finished', 'winner' => $winner]);
    session()->forget(['match_id', 'match_role']);

    broadcast(new MatchFinished($matchId, $winner ?? '不明'));

    return response()->json(['winner' => $winner]);
});

Route::get('/character', function (Request $request) {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

    $stats = [];
    foreach (array_keys(config('characters')) as $key) {
        $stats[$key] = $player->statsFor($key);
    }

    return CurrentPlayer::attach(
        response()->view('character', [
            'player' => $player,
            'characters' => config('characters'),
            'stats' => $stats,
            'cpus' => config('cpu'),
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

Route::get('/solo/{difficulty?}', function (Request $request, string $difficulty = 'normal') {
    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $stats = $player->currentStats();
    $cpu = config('cpu.' . $difficulty) ?? config('cpu.normal');

    return CurrentPlayer::attach(
        response()->view('solo', [
            'player' => $player,
            'characterName' => $stats->name(),
            'level' => $stats->level,
            'maxHp' => $stats->maxHp(),
            'power' => $stats->power(),
            'cpu' => $cpu,
            'difficulty' => $difficulty,
            'words' => WordList::forMatch('solo-' . Str::random(8)),
        ]),
        $player
    );
});

Route::post('/solo/result', function (Request $request) {
    $data = $request->validate([
        'won' => ['required', 'boolean'],
        'difficulty' => ['required', 'string'],
    ]);

    $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
    $stats = $player->currentStats();
    $cpu = config('cpu.' . $data['difficulty']) ?? config('cpu.normal');

    $gain = $data['won'] ? $cpu['exp_win'] : $cpu['exp_lose'];
    $leveled = $stats->addExp($gain);
    $stats->increment($data['won'] ? 'wins' : 'losses');

    return CurrentPlayer::attach(
        response()->json([
            'character' => $stats->name(),
            'gain' => $gain,
            'level' => $stats->level,
            'exp' => $stats->exp,
            'required' => $stats->requiredExp(),
            'leveled' => $leveled,
        ]),
        $player
    );
});
