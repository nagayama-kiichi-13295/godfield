<?php

namespace App\Http\Controllers;

use App\Support\CurrentPlayer;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function index(Request $request)
    {
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
                'owned' => $player->items()->get()->keyBy('item'),
            ]),
            $player
        );
    }

    public function select(Request $request)
    {
        $data = $request->validate(['character' => ['required', 'string']]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

        if (array_key_exists($data['character'], config('characters'))) {
            $player->update(['character' => $data['character']]);
            $player->statsFor($data['character']);
        }

        return CurrentPlayer::attach(redirect('/character'), $player);
    }

    public function allocate(Request $request)
    {
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
    }

    public function resetPoints(Request $request)
    {
        $data = $request->validate(['character' => ['required', 'string']]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));

        if (array_key_exists($data['character'], config('characters'))) {
            $player->statsFor($data['character'])->resetPoints();
        }

        return CurrentPlayer::attach(redirect('/character'), $player);
    }

    public function equip(Request $request)
    {
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

        if ($item !== null && ! $player->items()->where('item', $item)->exists()) {
            return CurrentPlayer::attach(redirect('/character'), $player);
        }

        $player->statsFor($data['character'])->equip($data['slot'], $item);

        return CurrentPlayer::attach(redirect('/character'), $player);
    }

    public function upgrade(Request $request)
    {
        $data = $request->validate(['item' => ['required', 'string']]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $row = $player->items()->where('item', $data['item'])->first();

        if ($row) {
            $row->upgrade();
        }

        return CurrentPlayer::attach(redirect('/character'), $player);
    }
}