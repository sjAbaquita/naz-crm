<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\BoardColumn;
use App\Models\Team;
use App\Models\User;

class BoardColumnPolicy
{
    /**
     * Determine whether the user can create columns.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    /**
     * Determine whether the user can reorder columns.
     */
    public function reorder(User $user, Team $team): bool
    {
        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    /**
     * Determine whether the user can update the column.
     */
    public function update(User $user, BoardColumn $column): bool
    {
        return $this->canManage($user, $column);
    }

    /**
     * Determine whether the user can delete the column.
     */
    public function delete(User $user, BoardColumn $column): bool
    {
        return $this->canManage($user, $column);
    }

    protected function canManage(User $user, BoardColumn $column): bool
    {
        $teamId = $column->board?->team_id;

        return $teamId !== null
            && ($user->teamRole(Team::find($teamId))?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
