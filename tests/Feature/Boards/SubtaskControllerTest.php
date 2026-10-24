<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\Subtask;
use App\Models\User;

test('assignee can create subtask for card', function () {
    [$team, $assignee] = teamUser(TeamRole::Member);
    $column = \App\Models\BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $card = BoardCard::factory()->create(['assigned_to' => $assignee->id, 'column_id' => $column->id]);

    $this->actingAs($assignee)
        ->post(route('subtasks.store', [$team, $card]), [
            'title' => 'New subtask',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('subtasks', [
        'board_card_id' => $card->id,
        'title' => 'New subtask',
        'created_by' => $assignee->id,
    ]);
});

test('non-creator cannot edit subtask', function () {
    [$team, $creator] = teamUser(TeamRole::Owner);
    [$team2, $otherUser] = teamUser(TeamRole::Member);
    $column = \App\Models\BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $card = BoardCard::factory()->create(['column_id' => $column->id]);
    $subtask = Subtask::factory()->create([
        'board_card_id' => $card->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($otherUser)
        ->put(route('subtasks.update', [$team, $card, $subtask]), [
            'title' => 'Hacked',
        ])
        ->assertForbidden();
});
