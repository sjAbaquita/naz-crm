<?php

namespace App\Actions\Subtask;

use App\Actions\Activity\RecordActivity;
use App\Models\Subtask;

class DeleteSubtask
{
    public function handle(Subtask $subtask): void
    {
        $card = $subtask->boardCard;

        app(RecordActivity::class)->handle(
            team: $card->column?->board?->team_id,
            action: 'deleted_subtask',
            description: "deleted subtask '{$subtask->title}' from card '{$card->title}'",
            subject: $card,
            properties: ['subtask_id' => $subtask->id],
        );

        $subtask->delete();
    }
}
