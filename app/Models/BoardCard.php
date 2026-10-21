<?php

namespace App\Models;

use App\Models\BoardColumn;
use Database\Factories\BoardCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['column_id', 'title', 'description', 'position'])]
class BoardCard extends Model
{	
	/** @use HasFactory<BoardCardFactory> */
    use HasFactory;

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class);
    }
}
