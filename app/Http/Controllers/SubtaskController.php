<?php

namespace App\Http\Controllers;

use App\Actions\Subtask\CreateSubtask;
use App\Actions\Subtask\DeleteSubtask;
use App\Actions\Subtask\UpdateSubtask;
use App\Http\Requests\StoreSubtaskRequest;
use App\Http\Requests\UpdateSubtaskRequest;
use App\Models\BoardCard;
use App\Models\Subtask;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    private function getCard(Team $team, int $cardId): BoardCard
    {
        return BoardCard::query()
            ->whereKey($cardId)
            ->whereHas('column', fn ($q) => $q->where('board_id', $team->board()->value('id')))
            ->firstOrFail();
    }

    public function index(Request $request, Team $currentTeam, int $card): JsonResponse
    {
        $card = $this->getCard($currentTeam, $card);

        $subtasks = $card->subtasks()
            ->with('creator:id,name', 'updater:id,name')
            ->get();

        return response()->json(['subtasks' => $subtasks]);
    }

    public function store(StoreSubtaskRequest $request, Team $currentTeam, int $card, CreateSubtask $createSubtask): JsonResponse | RedirectResponse
    {
        $card = $this->getCard($currentTeam, $card);

        $subtask = $createSubtask->handle($card, $request->validated(), $request->user()->id);

        // return response()->json(['subtask' => $subtask->load('creator:id,name')], 201);
		return to_route('board.index', $currentTeam);
    }

    public function update(UpdateSubtaskRequest $request, Team $currentTeam, int $card, Subtask $subtask, UpdateSubtask $updateSubtask): JsonResponse | RedirectResponse
    {
        $card = $this->getCard($currentTeam, $card);

        $subtask = $updateSubtask->handle($subtask, $request->validated(), $request->user()->id);

        // return response()->json(['subtask' => $subtask->load('creator:id,name', 'updater:id,name')]);
		return to_route('board.index', $currentTeam);
    }

    public function destroy(Request $request, Team $currentTeam, int $card, Subtask $subtask, DeleteSubtask $deleteSubtask): JsonResponse | RedirectResponse
    {
        if ($request->user()->id !== $subtask->created_by) {
            abort(403);
        }

        $deleteSubtask->handle($subtask);

        // return response()->json(['message' => 'Subtask deleted']);
		return to_route('board.index', $currentTeam);
    }
}
