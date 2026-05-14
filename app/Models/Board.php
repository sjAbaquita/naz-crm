<?php

namespace App\Models;

use App\Models\BoardColumn;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class Board extends Model
{
	/** @use HasFactory<TeamFactory> */
    use HasFactory;

    public function columns()
    {
        return $this->hasMany(BoardColumn::class, 'board_id')
            ->orderBy('position');
    }
}
