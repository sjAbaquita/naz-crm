<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Team;
use App\Models\User;

test('board index returns active team board with permissions', function () {
    [$team, $member] = teamUser(TeamRole::Member);

    BoardColumn::factory()->count(2)->create([
        'board_id' => $team->board->id,
    ]);

    $this->actingAs($member)
        ->get(route('board.index', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('boards/Index')
            ->where('permissions.canManageColumns', false)
            ->where('permissions.canEditCards', false)
            ->has('board.columns', 2)
        );
});

test('members can create and move cards but cannot edit or delete them', function () {
    [$team, $member, $card, $targetColumn] = boardScenario(TeamRole::Member);

    $this
        ->actingAs($member)
        ->post(route('cards.store', $team), [
            'column_id' => $targetColumn->id,
            'title' => 'Follow up',
            'description' => null,
        ])
        ->assertRedirect();

    $this
        ->actingAs($member)
        ->patch(route('cards.move', [$team, $card]), [
            'column_id' => $targetColumn->id,
            'position' => 0,
        ])
        ->assertRedirect();

    $this
        ->actingAs($member)
        ->put(route('cards.update', [$team, $card]), [
            'title' => 'Changed',
            'description' => null,
        ])
        ->assertForbidden();

    $this
        ->actingAs($member)
        ->delete(route('cards.destroy', [$team, $card]))
        ->assertForbidden();
});

test('a team cannot mutate another teams card', function () {
    [$team, $owner] = teamUser(TeamRole::Owner);
    $foreignCard = BoardCard::factory()->create();

    $this
        ->actingAs($owner)
        ->delete(route('cards.destroy', [$team, $foreignCard]))
        ->assertNotFound();
});

test('admins can create, reorder, and delete columns', function () {
    [$team, $admin] = teamUser(TeamRole::Admin);

    $this->actingAs($admin)
        ->post(route('column.store', $team), [
            'title' => 'In Progress',
        ])
        ->assertRedirect();

    $column = $team->board->columns()->where('title', 'In Progress')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('column.reorder', $team), [
            'columns' => [$column->id],
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('column.destroy', [$team, $column->id]))
        ->assertRedirect();
});

test('members cannot manage columns', function () {
    [$team, $member] = teamUser(TeamRole::Member);
    $column = BoardColumn::factory()->create(['board_id' => $team->board->id]);

    $this->actingAs($member)
        ->post(route('column.store', $team), ['title' => 'Test'])
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('column.reorder', $team), ['columns' => [$column->id]])
        ->assertForbidden();

    $this->actingAs($member)
        ->delete(route('column.destroy', [$team, $column->id]))
        ->assertForbidden();
});

/**
 * @return array{Team, User, BoardCard, BoardColumn}
 */
function boardScenario(TeamRole $role): array
{
    [$team, $user] = teamUser($role);

    $sourceColumn = BoardColumn::factory()->create([
        'board_id' => $team->board->id,
        'position' => 0,
    ]);
    $targetColumn = BoardColumn::factory()->create([
        'board_id' => $team->board->id,
        'position' => 1,
    ]);
    $card = BoardCard::factory()->create([
        'column_id' => $sourceColumn->id,
        'position' => 0,
    ]);

    return [$team, $user, $card, $targetColumn];
}

/**
 * @return array{Team, User}
 */
function teamUser(TeamRole $role): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->board()->create(['name' => 'Board']);
    $team->members()->attach($user, ['role' => $role->value]);

    return [$team, $user];
}
