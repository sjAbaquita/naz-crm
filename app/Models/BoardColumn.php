<?php

namespace App\Models;

use Database\Factories\BoardColumnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['board_id', 'title', 'position'])]
class BoardColumn extends Model
{
	/** @use HasFactory<BoardColumnFactory> */
	use HasFactory;

    public function cards()
    {
        return $this->hasMany(BoardCard::class, 'column_id')->orderBy('position');
    }
}
