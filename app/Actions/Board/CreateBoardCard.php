<?php

namespace App\Actions\Board;

use App\Models\BoardCard;
use App\Models\BoardColumn;
use Illuminate\Support\Facades\DB;

class CreateBoardCard
{
    /**
     * Create a new board card
     */
    public function handle(int $columnId, array $validatedData): BoardCard
    {
        return DB::transaction(function () use ($columnId, $validatedData) {
            $column = BoardColumn::findOrFail($columnId);
            $maxPosition = $column->cards()->max('position') ?? -1;

            return BoardCard::create([
                ...$validatedData,
                'column_id' => $columnId,
                'position' => $maxPosition + 1,
            ]);
        });
    }
}
