<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $role = $request->user()->teamRole($currentTeam);

        $board = $currentTeam->board()
            ->with([
                'columns' => fn ($q) => $q->orderBy('position'),
                'columns.cards' => fn ($q) => $q->orderBy('position'),
            ])
            ->firstOrFail();

        return Inertia::render('boards/Index', [
            'board' => $board,
            'permissions' => [
                'canManageColumns' => $role?->isAtLeast(TeamRole::Admin) ?? false,
                'canEditCards' => $role?->isAtLeast(TeamRole::Admin) ?? false,
            ],
        ]);
    }
}
