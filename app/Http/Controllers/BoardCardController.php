<?php

namespace App\Http\Controllers;

use App\Models\BoardCard;
use Illuminate\Http\Request;

class BoardCardController extends Controller
{
    public function store(Request $request)
    {
        $card = BoardCard::create([
            'column_id' => $request->column_id,
            'title' => $request->title,
            'description' => $request->description,
            'position' => BoardCard::where('column_id', $request->column_id)->max('position') + 1,
        ]);

        return response()->json($card);
    }

    // MOVE + REORDER (drag & drop core)
    public function move(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:cards,id',
            'column_id' => 'required|exists:columns,id',
            'position' => 'required|integer',
        ]);

        $card = BoardCard::findOrFail($request->card_id);

        $oldColumn = $card->column_id;
        $newColumn = $request->column_id;

        // If moving within same column → reorder only
        if ($oldColumn == $newColumn) {

            $cards = BoardCard::where('column_id', $newColumn)
                ->orderBy('position')
                ->get()
                ->values();

            $cards->splice($request->position, 0, [$card]);

            foreach ($cards as $index => $c) {
                $c->update(['position' => $index]);
            }

        } else {
            // Moving between columns

            // reorder old column
            BoardCard::where('column_id', $oldColumn)
                ->where('id', '!=', $card->id)
                ->orderBy('position')
                ->get()
                ->values()
                ->each(fn ($c, $i) => $c->update(['position' => $i]));

            // move card
            $card->update([
                'column_id' => $newColumn,
                'position' => $request->position
            ]);

            // fix new column order
            BoardCard::where('column_id', $newColumn)
                ->orderBy('position')
                ->get()
                ->values()
                ->each(fn ($c, $i) => $c->update(['position' => $i]));
        }

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $card = BoardCard::findOrFail($id);

        $card->update($request->only([
            'title',
            'description'
        ]));

        return response()->json($card);
    }

    public function destroy($id)
    {
        BoardCard::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
