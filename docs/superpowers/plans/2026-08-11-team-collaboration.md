# Team Collaboration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Deliver a team-scoped Board and Calendar with the agreed role permissions, a useful Dashboard, and a passing automated test suite.

**Architecture:** A team owns one Board; a CalendarEvent belongs directly to a team and records its creator. Policies enforce authorization from the active route team. Vue components show only permitted controls and submit all changes through Inertia.

**Tech Stack:** Laravel 13, PHP 8.3, Pest 4, SQLite, Inertia 3, Vue 3, TypeScript, SortableJS, FullCalendar.

## Global Constraints

- Each team has one active Board; no multiple-Board UI.
- Owners/admins can create, edit, move, and delete cards/events.
- Members can create cards/events, move cards, and reschedule events only.
- Every route query must be scoped to the current_team route parameter.
- Use Form Requests and Policies; add no dependencies.
- Legacy unscoped Boards stay preserved but excluded; existing teams receive a new Board.
- Commit every completed task when Git write access is available.

---

## File structure

- Migration: database/migrations/*_add_team_scoping_to_boards_and_calendar_events.php
- Models/actions: app/Models/{Team,Board,BoardColumn,BoardCard,CalendarEvent,User}.php and app/Actions/Teams/CreateTeam.php
- Security: app/Policies/{BoardPolicy,BoardColumnPolicy,BoardCardPolicy,CalendarEventPolicy}.php and app/Http/Requests/{Board,Calendar}/*
- HTTP/UI: Board, Calendar, Dashboard controllers/routes/pages and existing TypeScript types.
- Tests: tests/Feature/{Boards/BoardTest,Calendar/CalendarEventTest,DashboardTest,Teams/TeamTest}.php.

### Task 1: Restore database isolation and create team-owned records

**Files:**
- Modify: tests/Pest.php, Team/Board/CalendarEvent/User models, CreateTeam action, Board/CalendarEvent factories
- Create: database/migrations/YYYY_MM_DD_HHMMSS_add_team_scoping_to_boards_and_calendar_events.php
- Test: tests/Feature/Teams/TeamTest.php

**Interfaces:**
- Produces Team::board(): HasOne, Team::calendarEvents(): HasMany, Board::team(): BelongsTo, and CalendarEvent::team()/creator(): BelongsTo.

- [ ] **Step 1: Write the failing per-team Board test.**

~~~php
test('every created team receives exactly one board', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('teams.store'), ['name' => 'Operations']);

    $team = Team::where('name', 'Operations')->firstOrFail();
    expect($team->board)->toBeInstanceOf(Board::class)
        ->and($team->boards()->count())->toBe(1);
});
~~~

- [ ] **Step 2: Run it before the fix.**

Run: php artisan test tests/Feature/Teams/TeamTest.php --filter="every created team receives exactly one board"

Expected: FAIL with no such table: users.

- [ ] **Step 3: Enable the migrated in-memory test database and add the schema.**

~~~php
// tests/Pest.php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// app/Models/Team.php
public function board(): HasOne
{
    return $this->hasOne(Board::class);
}
~~~

Add nullable boards.team_id, calendar_events.team_id, and calendar_events.created_by foreign keys. Create one Board for each non-deleted existing team and add a unique index on boards.team_id. Add the relations and factory defaults.

- [ ] **Step 4: Create a Board in the existing CreateTeam transaction.**

~~~php
$team = Team::create(['name' => $name, 'is_personal' => $isPersonal]);
$team->board()->create(['name' => 'Board']);
~~~

- [ ] **Step 5: Verify and commit.**

Run: php artisan test tests/Feature/Teams/TeamTest.php

Expected: PASS.

~~~bash
git add tests/Pest.php database/migrations app/Models app/Actions/Teams/CreateTeam.php database/factories
git commit -m "feat: scope boards and events to teams"
~~~

### Task 2: Build Board authorization and persistent server actions

**Files:**
- Create: BoardPolicy, BoardColumnPolicy, BoardCardPolicy; StoreColumnRequest, UpdateColumnRequest, ReorderColumnsRequest, StoreCardRequest, UpdateCardRequest, MoveCardRequest; tests/Feature/Boards/BoardTest.php
- Modify: BoardController, BoardColumnController, BoardCardController, routes/boards.php

**Interfaces:**
- Produces named routes columns.store/update/reorder/destroy and cards.store/update/move/destroy.
- MoveCardRequest accepts column_id and position; the card comes only from route binding.

- [ ] **Step 1: Write failing role and isolation tests.**

~~~php
test('members can create and move cards but cannot edit or delete them', function () {
    [$team, $member, $card, $target] = boardScenario(TeamRole::Member);

    $this->actingAs($member)->post(route('cards.store', $team), [
        'column_id' => $target->id, 'title' => 'Follow up', 'description' => null,
    ])->assertRedirect();
    $this->actingAs($member)->patch(route('cards.update', [$team, $card]), ['title' => 'Changed'])
        ->assertForbidden();
    $this->actingAs($member)->delete(route('cards.destroy', [$team, $card]))->assertForbidden();
});
~~~

Define boardScenario in this test file: attach the supplied role to a user and create columns/cards on that team Board.

- [ ] **Step 2: Run the test before implementation.**

Run: php artisan test tests/Feature/Boards/BoardTest.php

Expected: FAIL because current Board routes/actions have no role enforcement.

- [ ] **Step 3: Implement Policies and narrow requests.**

~~~php
public function update(User $user, BoardCard $card): bool
{
    return $user->teamRole($card->column->board->team)?->isAtLeast(TeamRole::Admin) ?? false;
}
public function move(User $user, BoardCard $card): bool
{
    return $user->belongsToTeam($card->column->board->team);
}
~~~

Column management requires Admin+. Validate column/card titles as required strings of 1–255 characters, descriptions as nullable strings, and every position as integer min:0.

- [ ] **Step 4: Replace global JSON mutations with team-scoped redirect actions.**

~~~php
$board = $currentTeam->board()->with('columns.cards')->firstOrFail();
abort_unless($card->column->board_id === $board->id, 404);
$this->authorize('move', $card);
DB::transaction(fn () => $this->moveAndReindex($card, $targetColumn, $position));
return to_route('board.index', $currentTeam);
~~~

Implement moveAndReindex to remove the card from the source collection, insert it at the destination index, then write contiguous zero-based positions for each affected column.

- [ ] **Step 5: Verify and commit.**

Run: php artisan test tests/Feature/Boards/BoardTest.php; vendor/bin/pint --test

Expected: PASS.

~~~bash
git add app/Policies app/Http/Requests/Board app/Http/Controllers routes/boards.php tests/Feature/Boards
git commit -m "feat: persist team board workflows"
~~~

### Task 3: Complete the Board page

**Files:**
- Modify: resources/js/pages/boards/Index.vue, resources/js/types/boards.ts, BoardController
- Test: tests/Feature/Boards/BoardTest.php

**Interfaces:**
- Produces BoardPermissions containing canManageColumns and canEditCards.
- Consumes Task 2 routes and refreshes server state after every mutation.

- [ ] **Step 1: Add a failing payload assertion.**

~~~php
$this->actingAs($member)->get(route('board.index', $team))
    ->assertInertia(fn ($page) => $page
        ->component('boards/Index')
        ->where('permissions.canManageColumns', false)
        ->has('board.columns', 2));
~~~

- [ ] **Step 2: Run it before implementation.**

Run: php artisan test tests/Feature/Boards/BoardTest.php --filter="payload"

Expected: FAIL at permissions.

- [ ] **Step 3: Add server permissions and frontend types.**

~~~ts
export type BoardPermissions = {
    canManageColumns: boolean;
    canEditCards: boolean;
};
export type BoardCard = {
    id: number; column_id: number; title: string;
    description: string | null; position: number;
};
~~~

- [ ] **Step 4: Replace temporary data and console logging with Inertia calls.**

~~~ts
router.post(cards.store({ current_team: props.currentTeam.slug }).url, {
    column_id: columnId, title: form.title, description: form.description || null,
});
router.patch(cards.move({ current_team: props.currentTeam.slug, card: cardId }).url, {
    column_id: targetColumnId, position: newIndex,
});
~~~

Replace the Calendar-themed placeholder modal with a card title/description modal. Gate column controls with canManageColumns and card detail/delete controls with canEditCards. On Sortable errors call router.reload() to restore server truth.

- [ ] **Step 5: Verify and commit.**

Run: npm.cmd run types:check; npm.cmd run lint:check; npm.cmd run build

Expected: PASS.

~~~bash
git add resources/js/pages/boards/Index.vue resources/js/types/boards.ts app/Http/Controllers/BoardController.php tests/Feature/Boards
git commit -m "feat: complete board interactions"
~~~

### Task 4: Implement shared Calendar authorization and rescheduling

**Files:**
- Modify: CalendarEventPolicy, StoreCalendarEventRequest, UpdateCalendarEventRequest, CreateCalendarEvent, CalendarEventController, routes/calendar.php
- Create: RescheduleCalendarEventRequest and tests/Feature/Calendar/CalendarEventTest.php

**Interfaces:**
- Produces calendar.reschedule PATCH route with start_date/end_date.
- Detailed update/delete require Admin+; reschedule permits every team member.

- [ ] **Step 1: Write the failing Calendar role test.**

~~~php
test('members can create and reschedule team events but cannot edit or delete them', function () {
    [$team, $member] = teamUser(TeamRole::Member);
    $event = CalendarEvent::factory()->for($team)->create();

    $this->actingAs($member)->post(route('calendar.store', $team), eventPayload())->assertRedirect();
    $this->actingAs($member)->patch(route('calendar.reschedule', [$team, $event]), [
        'start_date' => '2026-08-14', 'end_date' => '2026-08-15',
    ])->assertRedirect();
    $this->actingAs($member)->put(route('calendar.update', [$team, $event]), eventPayload())
        ->assertForbidden();
});
~~~

- [ ] **Step 2: Run it before implementation.**

Run: php artisan test tests/Feature/Calendar/CalendarEventTest.php

Expected: FAIL because events are user-scoped and every policy denies access.

- [ ] **Step 3: Make Calendar data team-scoped and authorize each action.**

~~~php
$event = $currentTeam->calendarEvents()->create([
    ...$request->validated(),
    'created_by' => $request->user()->id,
]);
$this->authorize('reschedule', $calendar);
$calendar->update($request->validated());
~~~

Do not accept client team_id, owner_id, or created_by. Index via currentTeam calendarEvents with creator:id,name. Before every bound-event action, return 404 if it is outside the current team. The reschedule request validates only dates; its policy allows every member.

- [ ] **Step 4: Verify and commit.**

Run: php artisan test tests/Feature/Calendar/CalendarEventTest.php; php artisan test

Expected: PASS.

~~~bash
git add app/Policies/CalendarEventPolicy.php app/Http/Requests/Calendar app/Actions/Calendar app/Http/Controllers/CalendarEventController.php routes/calendar.php tests/Feature/Calendar
git commit -m "feat: share calendar events by team"
~~~

### Task 5: Finish Calendar UI and add the Dashboard overview

**Files:**
- Create: app/Http/Controllers/DashboardController.php
- Modify: routes/web.php, resources/js/pages/calendar/Index.vue, resources/js/pages/Dashboard.vue, resources/js/types/calendar-events.ts, tests/Feature/DashboardTest.php

**Interfaces:**
- Dashboard props: columnSummaries as id/title/cardCount and upcomingEvents as CalendarEvent list.
- Calendar prop: permissions.canEditEvents; all members retain create/reschedule.

- [ ] **Step 1: Write a failing dashboard payload test.**

~~~php
$this->actingAs($user)->get(route('dashboard', $team))
    ->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('columnSummaries', 2)
        ->has('upcomingEvents', 1));
~~~

- [ ] **Step 2: Run it before implementation.**

Run: php artisan test tests/Feature/DashboardTest.php --filter="overview"

Expected: FAIL because the dashboard route is static.

- [ ] **Step 3: Add the team overview controller and route.**

~~~php
$board = $currentTeam->board()->withCount('columns.cards')->firstOrFail();
$upcomingEvents = $currentTeam->calendarEvents()
    ->whereDate('start_date', '>=', today())->orderBy('start_date')->limit(5)->get();
~~~

Return these through Inertia, replacing only the current static dashboard action.

- [ ] **Step 4: Persist drag/resize and limit Calendar controls.**

~~~ts
function reschedule(event: EventApi, revert: () => void): void {
    router.patch(rescheduleRoute({ current_team: props.currentTeam.slug, calendar: event.id }).url, {
        start_date: toDateOnly(event.startStr),
        end_date: toDateOnly(event.endStr) || null,
    }, { onError: revert });
}
~~~

Wire this to FullCalendar eventDrop and eventResize. Keep create visible to members; render edit/delete only for canEditEvents, and make the details modal read-only otherwise.

- [ ] **Step 5: Replace Dashboard placeholders with column totals, five upcoming events, team Board/Calendar links, and empty states.**

- [ ] **Step 6: Run final verification and commit.**

Run: php artisan test; npm.cmd run types:check; npm.cmd run lint:check; npm.cmd run build; vendor/bin/pint --test

Expected: PASS for every command.

~~~bash
git add resources/js app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/DashboardTest.php
git commit -m "feat: add team dashboard overview"
~~~

## Plan self-review

- Spec coverage: Tasks 1–5 cover test repair, data model, Board, Calendar, authorization, team isolation, dashboard, UI, and full verification.
- Placeholder scan: all tasks include paths, interfaces, concrete commands, and expected outcomes.
- Type consistency: Board uses BoardPermissions, MoveCardRequest, and card route binding; Calendar uses RescheduleCalendarEventRequest and calendar.reschedule throughout.

