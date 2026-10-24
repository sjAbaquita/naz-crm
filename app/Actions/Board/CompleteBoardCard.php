<?php

namespace App\Actions\Board;

use App\Models\BoardCard;
use Exception;

class CompleteBoardCard
{
    /**
     * Mark a board card as completed if all subtasks are completed.
     * Throws an exception if there are any incomplete subtasks.
     */
    public function handle(BoardCard $card): BoardCard
    {
        // If there are any incomplete subtasks, prevent completion.
        $hasIncomplete = $card->subtasks()->where('is_completed', false)->exists();
        if ($hasIncomplete) {
            throw new Exception('Cannot complete card until all subtasks are finished.');
        }

        $card->is_completed = true;
        $card->save();

        return $card;
    }
}
