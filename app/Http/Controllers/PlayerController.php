<?php

namespace App\Http\Controllers;

use App\Support\CurrentPlayer;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function title()
    {
        return view('title');
    }

    public function form()
    {
        return view('player');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:20']]);

        $player = CurrentPlayer::resolve($request->cookie(CurrentPlayer::COOKIE));
        $player->update(['name' => $request->name]);

        return CurrentPlayer::attach(redirect('/character'), $player);
    }
}