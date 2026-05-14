<?php

namespace App\Models;

use App\Models\BoardColumn;
use Database\Factories\BoardCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['column_id', 'title', 'description', 'position'])]
class BoardCard extends Model
{	
	/** @use HasFactory<BoardCardFactory> */
    use HasFactory;

    public function columns()
    {
        return $this->hasMany(BoardColumn::class, 'column_id')->orderBy('position');
    }
}
