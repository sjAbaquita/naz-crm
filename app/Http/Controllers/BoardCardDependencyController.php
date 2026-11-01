<?php

namespace App\Http\Controllers;

use App\Models\BoardCard;
use App\Models\BoardCardDependency;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BoardCardDependencyController extends Controller
{
    public function store(Request $request, Team $currentTeam, BoardCard $card)
    {
        $validated = $request->validate([
            'depends_on_card_id' => 'required|exists:board_cards,id',
        ]);

        $dependsOnCard = BoardCard::findOrFail($validated['depends_on_card_id']);

        // Ensure both cards are in the same team
        if ($card->column->board->team_id !== $currentTeam->id || $dependsOnCard->column->board->team_id !== $currentTeam->id) {
            abort(403);
        }

        // Circular check
        if ($card->id === $dependsOnCard->id || $dependsOnCard->dependencies()->where('depends_on_card_id', $card->id)->exists()) {
            return back()->withErrors(['depends_on_card_id' => 'Cannot create circular dependency.']);
        }

        BoardCardDependency::create([
            'board_card_id' => $card->id,
            'depends_on_card_id' => $dependsOnCard->id,
        ]);

        return response()->json(['message' => 'Dependency created'], 201);
    }
}
