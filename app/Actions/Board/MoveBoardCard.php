<?php

namespace App\Actions\Board;

use App\Actions\Activity\RecordActivity;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use Illuminate\Support\Facades\DB;

class MoveBoardCard
{
    /**
     * Move a board card to a new column and/or position
     */
    public function handle(BoardCard $card, BoardColumn $targetColumn, int $newPosition): void
    {
        DB::transaction(function () use ($card, $targetColumn, $newPosition) {
            $sourceColumnId = $card->column_id;

            // If moving to a different column, first shift target column cards to make room
            if ($sourceColumnId !== $targetColumn->id) {
                $targetColumn->cards()
                    ->where('position', '>=', $newPosition)
                    ->increment('position');
            } else {
                // Moving within same column - handle direction
                $oldPosition = $card->position;
                if ($newPosition < $oldPosition) {
                    // Moving up - shift cards between new and old position down
                    $targetColumn->cards()
                        ->where('position', '>=', $newPosition)
                        ->where('position', '<', $oldPosition)
                        ->increment('position');
                } elseif ($newPosition > $oldPosition) {
                    // Moving down - shift cards between old and new position up
                    $targetColumn->cards()
                        ->where('position', '>', $oldPosition)
                        ->where('position', '<=', $newPosition)
                        ->decrement('position');
                }
            }

            // Update the moved card
            $card->update([
                'column_id' => $targetColumn->id,
                'position' => $newPosition,
            ]);

            // Re-index source column if different
            if ($sourceColumnId !== $targetColumn->id) {
                $sourceColumn = BoardColumn::find($sourceColumnId);
                if ($sourceColumn) {
                    foreach ($sourceColumn->cards()->orderBy('position')->get()->values() as $index => $item) {
                        $item->update(['position' => $index]);
                    }
                }
            }

            $teamId = $targetColumn->board?->team_id;
            if ($teamId) {
                app(RecordActivity::class)->handle(
                    team: $teamId,
                    action: 'moved_card',
                    description: "moved card '{$card->title}' to '{$targetColumn->title}'",
                    subject: $card,
                );
            }
        });
    }
}
