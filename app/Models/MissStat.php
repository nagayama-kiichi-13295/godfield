<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissStat extends Model
{
    protected $fillable = ['player_id', 'kana', 'count'];
}