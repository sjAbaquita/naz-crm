<?php

namespace App\Actions\Board;

use App\Models\BoardColumn;

class UpdateBoardColumn
{
    /**
     * Update a board column
     */
    public function handle(BoardColumn $column, array $validatedData): BoardColumn
    {
        $column->update($validatedData);

        return $column;
    }
}
