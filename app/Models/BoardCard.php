<?php

namespace App\Models;

use Database\Factories\BoardCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['column_id', 'title', 'description', 'position', 'assigned_to', 'start_date', 'due_date', 'priority', 'is_completed'])]
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

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'label_board_card');
    }

    public function subtasks()
    {
        return $this->hasMany(Subtask::class)->orderBy('position');
    }

    public function dependencies()
    {
        return $this->hasMany(BoardCardDependency::class, 'board_card_id');
    }

    public function dependents()
    {
        return $this->hasMany(BoardCardDependency::class, 'depends_on_card_id');
    }

    /**
     * Get the blocking cards for this card.
     * A card blocks another if it depends on that card.
     */
    public function blockingCards()
    {
        return $this->belongsToMany(
            BoardCard::class,
            'board_card_dependencies',
            'board_card_id',
            'depends_on_card_id'
        );
    }

    /**
     * Determine if the card can be marked complete.
     */
    public function getIsCompletableAttribute(): bool
    {
        return $this->subtasks()->where('is_completed', false)->doesNotExist();
    }

    /**
     * Get the team that owns the card.
     */
    public function getTeamAttribute(): ?Team
    {
        return $this->column?->board?->team;
    }
}