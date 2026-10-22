<?php

namespace App\Actions\Board;

use App\Models\BoardCard;

class UpdateBoardCard
{
    /**
     * Update a board card
     */
    public function handle(BoardCard $card, array $validatedData): BoardCard
    {
        $card->update($validatedData);

        return $card;
    }
}
