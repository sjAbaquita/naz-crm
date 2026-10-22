<?php

namespace App\Http\Controllers;

use App\Actions\Calendar\CreateCalendarEvent;
use App\Actions\Activity\RecordActivity;
use App\Enums\TeamRole;
use App\Http\Requests\Calendar\RescheduleCalendarEventRequest;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        $role = $request->user()->teamRole($currentTeam);

        return Inertia::render('calendar/Index', [
            'events' => $currentTeam->calendarEvents()
                ->with('creator:id,name')
                ->orderBy('start_date')
                ->get(),
            'permissions' => [
                'canEditEvents' => $role?->isAtLeast(TeamRole::Admin) ?? false,
            ],
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
    public function store(StoreCalendarEventRequest $request, Team $currentTeam, CreateCalendarEvent $createCalendarEvent): RedirectResponse
    {
        $createCalendarEvent->handle([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
            'owner_id' => $request->user()->id,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

		return to_route('calendar.index', $currentTeam);
    }

    // The following methods are retained for RESTful completeness but are not used in the current UI.
    // public function show(CalendarEvent $calendarEvent)
    // {
    //     // No implementation needed.
    // }

    // public function edit(CalendarEvent $calendarEvent)
    // {
    //     // No implementation needed.
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCalendarEventRequest $request, Team $currentTeam, CalendarEvent $calendar): RedirectResponse
    {
        $this->ensureTeamEvent($currentTeam, $calendar);
        Gate::authorize('update', $calendar);

        $calendar->update($request->validated());

        app(RecordActivity::class)->handle(
            team: $currentTeam->id,
            action: 'updated_event',
            description: "updated event '{$calendar->title}'",
            subject: $calendar,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event updated.')]);

        return to_route('calendar.index', $currentTeam);
    }

    /**
     * Reschedule the specified resource.
     */
    public function reschedule(RescheduleCalendarEventRequest $request, Team $currentTeam, CalendarEvent $calendar): RedirectResponse
    {
        $this->ensureTeamEvent($currentTeam, $calendar);
        Gate::authorize('reschedule', $calendar);

        $calendar->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event rescheduled.')]);

        return to_route('calendar.index', $currentTeam);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $currentTeam, CalendarEvent $calendar): RedirectResponse
    {
        $this->ensureTeamEvent($currentTeam, $calendar);
        Gate::authorize('delete', $calendar);

		$calendar->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event deleted.')]);

        return to_route('calendar.index', $currentTeam);
    }

    protected function ensureTeamEvent(Team $currentTeam, CalendarEvent $calendar): void
    {
        abort_unless($calendar->team_id === $currentTeam->id, 404);
    }
}
