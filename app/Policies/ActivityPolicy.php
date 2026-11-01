<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use App\Models\Team;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        return $user->belongsToTeam($activity->team);
    }
}