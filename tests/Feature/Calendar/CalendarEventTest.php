<?php

use App\Enums\TeamRole;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;

test('members can create and reschedule team events but cannot edit or delete them', function () {
    [$team, $member] = calendarTeamUser(TeamRole::Member);
    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'created_by' => $member->id,
        'owner_id' => $member->id,
    ]);

    $payload = ['title' => 'Planning', 'description' => null, 'location' => null, 'event_level' => 'primary', 'start_date' => '2026-08-14', 'end_date' => '2026-08-15'];

    $this->actingAs($member)->post(route('calendar.store', $team), $payload)->assertRedirect();
    $this->actingAs($member)->patch(route('calendar.reschedule', [$team, $event]), [
        'start_date' => '2026-08-16', 'end_date' => '2026-08-17',
    ])->assertRedirect();
    $this->actingAs($member)->put(route('calendar.update', [$team, $event]), $payload)->assertForbidden();
    $this->actingAs($member)->delete(route('calendar.destroy', [$team, $event]))->assertForbidden();
});

/** @return array{Team, User} */
function calendarTeamUser(TeamRole $role): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);

    return [$team, $user];
}
