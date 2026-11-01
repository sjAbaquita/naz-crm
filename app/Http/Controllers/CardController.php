<?php

namespace App\Http\Controllers;

use App\Models\BoardCard;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    public function activity(Team $currentTeam, int $cardId): JsonResponse
    {
        $card = BoardCard::query()->whereKey($cardId)->firstOrFail();

        $card->load(['attachments', 'subtasks']);

        return response()->json(['card' => $card], 200);
    }
}