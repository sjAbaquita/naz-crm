<?php

namespace App\Actions\Calendar;

use App\Models\CalendarEvent;
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

            return $event;
        });

	}

}