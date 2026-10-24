<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['board_card_id', 'depends_on_card_id'])]
class BoardCardDependency extends Model
{
    /** @use HasFactory<BoardCardDependencyFactory> */
    use HasFactory;

    public function boardCard(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class);
    }

    public function dependsOnCard(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'depends_on_card_id');
    }
}