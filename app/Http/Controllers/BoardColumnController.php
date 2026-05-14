<?php

namespace App\Http\Controllers;

use App\Models\BoardColumn;
use Illuminate\Http\Request;

class BoardColumnController extends Controller
{
    public function store(Request $request)
    {
        $column = BoardColumn::create([
            'board_id' => $request->board_id,
            'title' => $request->title,
            'position' => BoardColumn::where('board_id', $request->board_id)->max('position') + 1,
        ]);

        return response()->json($column);
    }

    // reorder columns (drag left/right)
    public function reorder(Request $request)
    {
        foreach ($request->columns as $index => $columnId) {
            BoardColumn::where('id', $columnId)->update([
                'position' => $index
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        BoardColumn::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
