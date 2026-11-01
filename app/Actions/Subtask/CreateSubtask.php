<?php

namespace App\Actions\Subtask;

use App\Actions\Activity\RecordActivity;
use App\Models\BoardCard;
use App\Models\Subtask;

class CreateSubtask
{
    public function handle(BoardCard $card, array $data, ?int $userId = null): Subtask
    {
        $maxPosition = $card->subtasks()->max('position') ?? -1;

        $subtask = $card->subtasks()->create([
            'title' => $data['title'],
            'position' => $maxPosition + 1,
            'created_by' => $userId,
            'is_completed' => false,
        ]);

        app(RecordActivity::class)->handle(
            team: $card->column?->board?->team_id,
            action: 'created_subtask',
            description: "created subtask '{$subtask->title}' for card '{$card->title}'",
            subject: $card,
            properties: ['subtask_id' => $subtask->id],
        );

        return $subtask;
    }
}