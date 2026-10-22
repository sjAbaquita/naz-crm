<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\Team;
use App\Models\User;

class BoardCardPolicy
{
    /**
     * Determine whether the user can create cards.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can move the card.
     */
    public function move(User $user, BoardCard $card): bool
    {
        $team = $card->column?->board?->team;

        return $team !== null && $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can update the card.
     */
    public function update(User $user, BoardCard $card): bool
    {
        return $this->canManage($user, $card);
    }

    /**
     * Determine whether the user can delete the card.
     */
    public function delete(User $user, BoardCard $card): bool
    {
        return $this->canManage($user, $card);
    }

    protected function canManage(User $user, BoardCard $card): bool
    {
        $team = $card->column?->board?->team;

        return $team !== null
            && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
