<?php

namespace App\Actions\Subtask;

use App\Actions\Activity\RecordActivity;
use App\Models\Subtask;

class UpdateSubtask
{
    public function handle(Subtask $subtask, array $data, ?int $userId = null): Subtask
    {
        $updateData = [];

        if (array_key_exists('title', $data)) {
            $updateData['title'] = $data['title'];
        }

        if (array_key_exists('is_completed', $data)) {
            $updateData['is_completed'] = $data['is_completed'];
        }

        if ($userId !== null) {
            $updateData['updated_by'] = $userId;
        }

        $card = $subtask->boardCard;
        $subtask->update($updateData);

        $activityAction = (isset($data['is_completed']) && $data['is_completed'] === true) ? 'completed_subtask' : 'updated_subtask';
        $activityDescription = (isset($data['is_completed']) && $data['is_completed'] === true)
            ? "completed subtask '{$subtask->title}'"
            : "updated subtask '{$subtask->title}'";

        app(RecordActivity::class)->handle(
            team: $card->column?->board?->team_id,
            action: $activityAction,
            description: $activityDescription,
            subject: $card,
            properties: ['subtask_id' => $subtask->id],
        );

        return $subtask->fresh();
    }
}