<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerItem extends Model
{
    protected $fillable = ['player_id', 'item'];

    public function config(): ?array
    {
        return config('equipment.items.' . $this->item);
    }
}