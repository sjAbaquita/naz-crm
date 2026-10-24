<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\BoardCardDependency;
use App\Models\Team;
use App\Models\User;

test('can create blocking dependency between cards', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $column = BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $cardA = BoardCard::factory()->create(['column_id' => $column->id]);
    $cardB = BoardCard::factory()->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->post(route('cards.dependencies.store', [$team, $cardA]), [
            'depends_on_card_id' => $cardB->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('board_card_dependencies', [
        'board_card_id' => $cardA->id,
        'depends_on_card_id' => $cardB->id,
    ]);
});

test('cannot create circular dependency', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $column = BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $cardA = BoardCard::factory()->create(['column_id' => $column->id]);
    $cardB = BoardCard::factory()->create(['column_id' => $column->id]);

    // A depends on B
    BoardCardDependency::create([
        'board_card_id' => $cardA->id,
        'depends_on_card_id' => $cardB->id,
    ]);

    // Try B depends on A (circular)
    $this->actingAs($user)
        ->post(route('cards.dependencies.store', [$team, $cardB]), [
            'depends_on_card_id' => $cardA->id,
        ])
        ->assertSessionHasErrors('depends_on_card_id');
});

test('cannot create cross-team dependency', function () {
    [$team1, $user1] = teamUser(TeamRole::Owner);
    [$team2, $user2] = teamUser(TeamRole::Owner);

    $column1 = BoardColumn::factory()->create(['board_id' => $team1->board->id]);
    $column2 = BoardColumn::factory()->create(['board_id' => $team2->board->id]);

    $cardA = BoardCard::factory()->create(['column_id' => $column1->id]);
    $cardB = BoardCard::factory()->create(['column_id' => $column2->id]);

    $this->actingAs($user1)
        ->post(route('cards.dependencies.store', [$team1, $cardA]), [
            'depends_on_card_id' => $cardB->id,
        ])
        ->assertForbidden();
});
