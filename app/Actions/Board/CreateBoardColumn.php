<?php

namespace App\Actions\Board;

use App\Models\BoardColumn;
use Illuminate\Support\Facades\DB;

class CreateBoardColumn
{
    /**
     * Create a new board column
     */
    public function handle(int $boardId, array $validatedData): BoardColumn
    {
        return DB::transaction(function () use ($boardId, $validatedData) {
            $maxPosition = BoardColumn::where('board_id', $boardId)->max('position') ?? -1;

            return BoardColumn::create([
                ...$validatedData,
                'board_id' => $boardId,
                'position' => $maxPosition + 1,
            ]);
        });
    }
}
