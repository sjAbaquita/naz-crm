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
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubtaskController extends Controller
{
    public function index(Request $request, Team $currentTeam, BoardCard $card): JsonResponse
    {
        $subtasks = $card->subtasks()
            ->with('creator:id,name', 'updater:id,name')
            ->get();

        return response()->json(['subtasks' => $subtasks]);
    }

    public function store(StoreSubtaskRequest $request, Team $currentTeam, BoardCard $card, CreateSubtask $createSubtask): JsonResponse
    {
        $subtask = $createSubtask->handle($card, $request->validated(), $request->user()->id);

        return response()->json(['subtask' => $subtask->load('creator:id,name')], 201);
    }

    public function update(UpdateSubtaskRequest $request, Team $currentTeam, BoardCard $card, Subtask $subtask, UpdateSubtask $updateSubtask): JsonResponse
    {
        $subtask = $updateSubtask->handle($subtask, $request->validated(), $request->user()->id);

        return response()->json(['subtask' => $subtask->load('creator:id,name', 'updater:id,name')]);
    }

    public function destroy(Request $request, Team $currentTeam, BoardCard $card, Subtask $subtask, DeleteSubtask $deleteSubtask): JsonResponse
    {
        if ($request->user()->id !== $subtask->created_by) {
            abort(403);
        }

        $deleteSubtask->handle($subtask);

        return response()->json(['message' => 'Subtask deleted']);
    }
}
