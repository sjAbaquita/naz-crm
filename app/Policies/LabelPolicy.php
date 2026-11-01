<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Label;
use App\Models\Team;
use App\Models\User;

class LabelPolicy
{
    /**
     * Determine whether the user can view labels.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create labels.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    /**
     * Determine whether the user can update the label.
     */
    public function update(User $user, Label $label, Team $team): bool
    {
        return $label->team_id === $team->id
            && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }

    /**
     * Determine whether the user can delete the label.
     */
    public function delete(User $user, Label $label, Team $team): bool
    {
        return $label->team_id === $team->id
            && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}