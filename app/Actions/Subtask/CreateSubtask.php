<?php

namespace App\Actions\Subtask;

use App\Models\BoardCard;
use App\Models\Subtask;

class CreateSubtask
{
    public function handle(BoardCard $card, array $data, ?int $userId = null): Subtask
    {
        $maxPosition = $card->subtasks()->max('position') ?? -1;

        return $card->subtasks()->create([
            'title' => $data['title'],
            'position' => $maxPosition + 1,
            'created_by' => $userId,
            'is_completed' => false,
        ]);
    }
}
