<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityFeedController extends Controller
{
    /**
     * Show the activity feed for a given team.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        // Ensure the authenticated user is a member of the team.
        Gate::authorize('viewAny', [Activity::class, $currentTeam]);

        $activities = $currentTeam->activities()
            ->with(['user:id,name', 'subject'])
            ->latest()
            ->limit(100)
            ->get();

        return Inertia::render('ActivityFeed', [
            'activities' => $activities,
            'team' => $currentTeam,
        ]);
    }
}
