<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;

test('guests are redirected to the login page', function () {
    $response = $this->get('/non-existent-team/dashboard');
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and see team overview', function () {
    [$team, $user] = dashboardTeamUser(TeamRole::Member);

    // Create board columns and cards for the team
    $column1 = BoardColumn::factory()->create(['board_id' => $team->board->id, 'position' => 0]);
    BoardCard::factory()->count(2)->create(['column_id' => $column1->id]);

    $column2 = BoardColumn::factory()->create(['board_id' => $team->board->id, 'position' => 1]);
    BoardCard::factory()->count(1)->create(['column_id' => $column2->id]);

    // Create upcoming calendar events for the team
    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'start_date' => Carbon::now()->addDays(1)->toDateString(),
        'end_date' => Carbon::now()->addDays(2)->toDateString(),
    ]);

    $this->actingAs($user)->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('columnSummaries', 2)
            ->has('columnSummaries.0', fn ($json) => $json->where('id', $column1->id)->where('title', $column1->title)->where('cardCount', 2)->etc())
            ->has('columnSummaries.1', fn ($json) => $json->where('id', $column2->id)->where('title', $column2->title)->where('cardCount', 1)->etc())
            ->has('upcomingEvents', 1)
            ->has('upcomingEvents.0', fn ($json) => $json->hasAll(['id', 'title', 'start_date', 'end_date', 'event_level', 'creator'])->etc())
        );
});

/**
 * @return array{Team, User}
 */
function dashboardTeamUser(TeamRole $role): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->board()->create(['name' => 'Board']);
    $team->members()->attach($user, ['role' => $role->value]);

    return [$team, $user];
}
