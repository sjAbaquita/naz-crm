<?php

namespace App\Actions\Calendar;

use App\Models\CalendarEvent;
use App\Models\Team;
use App\Notifications\EventCreated;
use Illuminate\Support\Facades\DB;

class CreateCalendarEvent
{
    /**
     * Create a new calendar event
     */
    public function handle(array $validatedData): CalendarEvent
    {
        return DB::transaction(function () use ($validatedData) {
            $event = CalendarEvent::create($validatedData);

            $team = Team::find($validatedData['team_id']);
            if ($team) {
                foreach ($team->members as $member) {
                    if ($member->id !== (int) $validatedData['created_by']) {
                        $member->notify(new EventCreated($event));
                    }
                }
                // Record activity for creation
                app(\App\Actions\Activity\RecordActivity::class)->handle(
                    team: $team->id,
                    action: 'created_event',
                    description: "created event '{$event->title}'",
                    subject: $event,
                );
            }

            return $event;
        });
    }
}
