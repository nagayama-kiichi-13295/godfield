<?php

use App\Models\GameMatch;
use App\Events\MatchStarted;
use App\Events\PlayerProgressed;
use App\Support\WordList;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('title');
});
Route::get('/matching', function () {
    return view('matching', [
        'name' => session('player_name')
    ]);
});

Route::get('/match/{matchId}', function (string $matchId) {
    return view('match', [
        'matchId' => $matchId,
        'words' => WordList::forMatch($matchId),
    ]);
});

Route::post('/match/{matchId}/start', function (string $matchId) {
    $startAt = (int) (microtime(true) * 1000) + 4000;

    broadcast(new MatchStarted($matchId, $startAt));

    return response()->json(['start_at' => $startAt]);
});

Route::get('/player', function () {
    return view('player');
});

Route::post('/player', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name' => ['required', 'string', 'max:20'],
    ]);

    session(['player_name' => $request->name]);

    return redirect('/matching');
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
