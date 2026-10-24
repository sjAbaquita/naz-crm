<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityFeedController extends Controller
{
    /**
     * Show the activity feed for a given team.
     */
    public function index(Request $request, Team $team): Response
    {
        // Ensure the authenticated user is a member of the team.
        $user = $request->user();
        abort_unless($user && $user->belongsToTeam($team), 403);

        $activities = $team->activities()
            ->with(['user:id,name', 'subject'])
            ->latest()
            ->limit(100)
            ->get();

        return Inertia::render('ActivityFeed', [
            'activities' => $activities,
            'team' => $team,
        ]);
    }
}
