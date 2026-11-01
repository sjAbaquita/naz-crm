<?php

use App\Models\BoardCard;
use App\Models\Subtask;
use App\Models\User;
use App\Models\BoardCardDependency;

test('board card has subtasks relationship', function () {
    $card = BoardCard::factory()->create();
    Subtask::factory()->create(['board_card_id' => $card->id]);

    expect($card->subtasks)->toHaveCount(1);
});

test('board card has blocking dependencies relationship', function () {
    $card = BoardCard::factory()->create();
    $blockingCard = BoardCard::factory()->create();
    BoardCardDependency::create([
        'board_card_id' => $card->id,
        'depends_on_card_id' => $blockingCard->id,
    ]);

    expect($card->dependencies)->toHaveCount(1)
        ->and($card->dependencies->first()->depends_on_card_id)->toBe($blockingCard->id);
});

test('subtask model has board card relationship', function () {
    $card = BoardCard::factory()->create();
    $subtask = Subtask::factory()->create(['board_card_id' => $card->id]);

    expect($subtask->boardCard->id)->toBe($card->id);
});

test('subtask creator can be retrieved', function () {
    $user = User::factory()->create();
    $card = BoardCard::factory()->create();
    $subtask = Subtask::factory()->create([
        'board_card_id' => $card->id,
        'created_by' => $user->id,
    ]);

    expect($subtask->creator->id)->toBe($user->id);
});