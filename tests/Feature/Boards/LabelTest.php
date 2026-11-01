<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Label;
use App\Models\Team;
use App\Models\User;

test('board index loads cards with attached labels', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->board()->create(['name' => 'Board']);
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $column = BoardColumn::factory()->create([
        'board_id' => $team->board->id,
        'position' => 0,
    ]);

    $card = BoardCard::factory()->create([
        'column_id' => $column->id,
        'position' => 0,
    ]);

    $label = Label::factory()->create([
        'team_id' => $team->id,
        'name' => 'Bug',
        'color' => '#ef4444',
    ]);

    $card->labels()->attach($label);

    $this->actingAs($user)
        ->get(route('board.index', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('boards/Index')
            ->has('board.columns.0.cards.0.labels', 1)
            ->where('board.columns.0.cards.0.labels.0.name', 'Bug')
            ->where('board.columns.0.cards.0.labels.0.color', '#ef4444')
        );
});

test('users can create, update, and delete labels for a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    // Store
    $this->actingAs($user)
        ->post(route('labels.store', $team), [
            'name' => 'Feature',
            'color' => '#3b82f6',
        ])
        ->assertRedirect(route('board.index', $team));

    $this->assertDatabaseHas('labels', [
        'team_id' => $team->id,
        'name' => 'Feature',
        'color' => '#3b82f6',
    ]);

    $label = Label::where('name', 'Feature')->firstOrFail();

    // Update
    $this->actingAs($user)
        ->put(route('labels.update', [$team, $label]), [
            'name' => 'High Priority',
            'color' => '#dc2626',
        ])
        ->assertRedirect(route('board.index', $team));

    $this->assertDatabaseHas('labels', [
        'id' => $label->id,
        'name' => 'High Priority',
        'color' => '#dc2626',
    ]);

    // Destroy
    $this->actingAs($user)
        ->delete(route('labels.destroy', [$team, $label]))
        ->assertRedirect(route('board.index', $team));

    $this->assertDatabaseMissing('labels', [
        'id' => $label->id,
    ]);
});