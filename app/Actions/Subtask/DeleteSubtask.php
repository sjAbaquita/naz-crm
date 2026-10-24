<?php

namespace App\Actions\Subtask;

use App\Models\Subtask;

class DeleteSubtask
{
    public function handle(Subtask $subtask): void
    {
        $subtask->delete();
    }
}
