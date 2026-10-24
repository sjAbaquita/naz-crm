<?php

namespace App\Actions\Subtask;

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

        $subtask->update($updateData);

        return $subtask->fresh();
    }
}
