<?php

namespace App\Http\Controllers;

use App\Actions\Calendar\CreateCalendarEvent;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

		// Current: only user events can be seen
		// TODO: change to team view
        return Inertia::render('calendar/Index', [
            'events' => $user->calendarEvents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCalendarEventRequest $request, CreateCalendarEvent $createCalendarEvent): RedirectResponse
    {
        $createCalendarEvent->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

		return to_route('calendar.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(CalendarEvent $calendarEvent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CalendarEvent $calendarEvent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCalendarEventRequest $request, string $currentTeam, CalendarEvent $calendar): RedirectResponse
    {
		$calendar->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event updated.')]);

        return to_route('calendar.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $currentTeam, CalendarEvent $calendar): RedirectResponse
    {
		$calendar->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event deleted.')]);

        return to_route('calendar.index');
    }
}
