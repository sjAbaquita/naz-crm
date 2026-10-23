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
                'columns.cards' => fn ($q) => $q->orderBy('position')->with('assignee:id,name,email', 'labels'),
            ])
            ->firstOrFail();

        // Get team members for assignment dropdown
        $teamMembers = $currentTeam->members()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->values();

        // Get recent activities for the notification bell
        $recentActivities = $currentTeam->activities()
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('boards/Index', [
            'board' => $board,
            'teamMembers' => $teamMembers,
            'recentActivities' => $recentActivities,
            'permissions' => [
                'canManageColumns' => $role?->isAtLeast(TeamRole::Admin) ?? false,
                'canEditCards' => $role?->isAtLeast(TeamRole::Admin) ?? false,
            ],
        ]);
    }
}
