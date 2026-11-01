<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Team;
use App\Models\User;

test('team member can view task detail page', function () {
    [$team, $user] = teamUser(TeamRole::Member);
    $column = BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $card = BoardCard::factory()->create([
        'column_id' => $column->id,
    ]);

    $this->actingAs($user)
        ->get(route('tasks.show', [$team, $card]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Show')
            ->where('card.id', $card->id)
        );
});

test('non-member cannot view task detail page', function () {
    [$team, $user] = teamUser(TeamRole::Member);
    $column = BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $otherUser = User::factory()->create();
    $card = BoardCard::factory()->create([
        'column_id' => $column->id,
    ]);

    $this->actingAs($otherUser)
        ->get(route('tasks.show', [$team, $card]))
        ->assertForbidden();
});
