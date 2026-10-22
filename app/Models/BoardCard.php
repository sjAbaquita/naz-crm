<?php

namespace App\Models;

use Database\Factories\BoardCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['column_id', 'title', 'description', 'position', 'assigned_to'])]
class BoardCard extends Model
{
    /** @use HasFactory<BoardCardFactory> */
    use HasFactory;

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the team that owns the card.
     */
    public function getTeamAttribute(): ?Team
    {
        return $this->column?->board?->team;
    }
}
