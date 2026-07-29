<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'player1_id', 'player2_id', 'winner_id',
        'player1', 'player2', 'winner',
        'player1_char', 'player2_char',
        'player1_level', 'player2_level',
        'status',
    ];
}