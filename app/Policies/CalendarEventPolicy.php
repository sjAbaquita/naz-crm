<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->team !== null && $user->belongsToTeam($calendarEvent->team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->canManage($user, $calendarEvent);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->canManage($user, $calendarEvent);
    }

    /**
     * Determine whether the user can reschedule the model.
     */
    public function reschedule(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->team !== null && $user->belongsToTeam($calendarEvent->team);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CalendarEvent $calendarEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CalendarEvent $calendarEvent): bool
    {
        return false;
    }

    protected function canManage(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->team !== null
            && ($user->teamRole($calendarEvent->team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
