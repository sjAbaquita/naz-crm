<?php

namespace App\Actions\Board;

use App\Actions\Activity\RecordActivity;
use App\Models\BoardCard;
use App\Notifications\CardAssigned;

class UpdateBoardCard
{
    /**
     * Update a board card
     */
    public function handle(BoardCard $card, array $validatedData): BoardCard
    {
        $oldAssignedTo = $card->assigned_to;

        $card->update($validatedData);

        if ($card->assigned_to && $card->assigned_to !== $oldAssignedTo && $card->assignee) {
            $card->assignee->notify(new CardAssigned($card));
        }

        $teamId = $card->column?->board?->team_id;
        if ($teamId) {
            app(RecordActivity::class)->handle(
                team: $teamId,
                action: 'updated_card',
                description: "updated card '{$card->title}'",
                subject: $card,
            );
        }

        return $card;
    }
}
