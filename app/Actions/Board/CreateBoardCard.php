<?php

namespace App\Actions\Board;

use App\Actions\Activity\RecordActivity;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Notifications\CardAssigned;
use Illuminate\Support\Facades\DB;

class CreateBoardCard
{
    /**
     * Create a new board card
     */
    public function handle(int $columnId, array $validatedData): BoardCard
    {
        return DB::transaction(function () use ($columnId, $validatedData) {
            $column = BoardColumn::with('board')->findOrFail($columnId);
            $maxPosition = $column->cards()->max('position') ?? -1;

            $card = BoardCard::create([
                ...$validatedData,
                'column_id' => $columnId,
                'position' => $maxPosition + 1,
            ]);

            if ($card->assigned_to && $card->assignee) {
                $card->assignee->notify(new CardAssigned($card));
            }

            if ($column->board?->team_id) {
                app(RecordActivity::class)->handle(
                    team: $column->board->team_id,
                    action: 'created_card',
                    description: "created card '{$card->title}' in '{$column->title}'",
                    subject: $card,
                );
            }

            return $card;
        });
    }
}
