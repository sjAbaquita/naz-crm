<?php

namespace App\Actions\Board;

use App\Models\Board;
use Illuminate\Support\Facades\DB;

class ReorderBoardColumns
{
    /**
     * Reorder board columns
     */
    public function handle(Board $board, array $columnIds): void
    {
        DB::transaction(function () use ($board, $columnIds) {
            foreach ($columnIds as $index => $columnId) {
                $board->columns()->where('id', $columnId)->update([
                    'position' => $index,
                ]);
            }
        });
    }
}
