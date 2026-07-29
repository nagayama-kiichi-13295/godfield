<?php

namespace App\Support;

use App\Models\Player;
use Illuminate\Support\Str;

class CurrentPlayer
{
    public const COOKIE = 'player_token';

    public static function resolve(?string $token): Player
    {
        if ($token) {
            $found = Player::where('token', $token)->first();

            if ($found) {
                return $found;
            }
        }

        return Player::create([
            'token' => Str::random(48),
            'character' => array_key_first(config('characters')),
        ]);
    }

    public static function attach($response, Player $player)
    {
        return $response->cookie(self::COOKIE, $player->token, 60 * 24 * 365);
    }
}