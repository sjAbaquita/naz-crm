<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function show(Request $request, Team $currentTeam, int $cardId): Response
    {
        $role = $request->user()->teamRole($currentTeam);

        $card = BoardCard::query()->with('attachments')
            ->whereKey($cardId)
            ->whereHas('column', fn ($q) => $q->where('board_id', $currentTeam->board()->value('id')))
            ->firstOrFail();

        $card->load([
            'subtasks' => fn ($q) => $q->orderBy('position')->with('creator:id,name', 'updater:id,name'),
            'labels',
            'assignee:id,name,email',
            'dependencies' => fn ($q) => $q->with('labels'),
            'column.board.team',
        ]);

        return Inertia::render('tasks/Show', [
            'card' => $card,
            'team' => $currentTeam,
            'permissions' => [
                'canEditCards' => $role?->isAtLeast(TeamRole::Admin) ?? false,
                'canManageSubtasks' => $request->user()->id === $card->created_by || $request->user()->id === $card->assigned_to,
            ],
        ]);
    }
}
