<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Team;
use App\Enums\TeamRole;

class AttachmentPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Team $team): bool
    {
        $membership = $user->memberships()->where('team_id', $team->id)->first();
        return $membership !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        return $this->canAccess($user, $attachment);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        return $this->canAccess($user, $attachment);
    }

    protected function canAccess(User $user, Attachment $attachment): bool
    {
        $card = $attachment->boardCard;
        if (!$card) return false;

        $team = $card->team;
        if (!$team) return false;

        $membership = $user->memberships()->where('team_id', $team->id)->first();
        if (!$membership) return false;

        // Admin/Owner can access
        if ($membership->role->isAtLeast(TeamRole::Admin)) {
            return true;
        }

        // Creator or Assignee
        return $attachment->uploaded_by === $user->id || $card->assigned_to === $user->id;
    }
}
