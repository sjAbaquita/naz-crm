<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $currentTeam): Response
    {
        $board = $currentTeam->board()->with(['columns.cards'])->firstOrFail();

        $columnSummaries = $board->columns->map(function ($column) {
            return [
                'id' => $column->id,
                'title' => $column->title,
                'cardCount' => $column->cards->count(),
            ];
        });

        $upcomingEvents = $currentTeam->calendarEvents()
            ->with('creator:id,name')
            ->whereDate('start_date', '>=', Carbon::today()->toDateString())
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'columnSummaries' => $columnSummaries,
            'upcomingEvents' => $upcomingEvents,
            'currentTeam' => $currentTeam,
        ]);
    }
}
