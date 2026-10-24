<?php

use App\Actions\Board\CompleteBoardCard;
use App\Actions\Subtask\CreateSubtask;
use App\Actions\Subtask\DeleteSubtask;
use App\Actions\Subtask\UpdateSubtask;
use App\Models\BoardCard;
use App\Models\Subtask;
use App\Models\User;

test('CreateSubtask action creates subtask with auto-increment position', function () {
    $user = User::factory()->create();
    $card = BoardCard::factory()->create();

    $action = new CreateSubtask();
    $subtask1 = $action->handle($card, ['title' => 'First subtask'], $user->id);
    $subtask2 = $action->handle($card, ['title' => 'Second subtask'], $user->id);

    expect($subtask1->title)->toBe('First subtask')
        ->and($subtask1->position)->toBe(0)
        ->and($subtask1->created_by)->toBe($user->id)
        ->and($subtask2->title)->toBe('Second subtask')
        ->and($subtask2->position)->toBe(1);
});

test('UpdateSubtask action updates title and completion state', function () {
    $user = User::factory()->create();
    $subtask = Subtask::factory()->create(['is_completed' => false, 'title' => 'Old Title']);

    $action = new UpdateSubtask();
    $updated = $action->handle($subtask, [
        'title' => 'New Title',
        'is_completed' => true,
    ], $user->id);

    expect($updated->title)->toBe('New Title')
        ->and($updated->is_completed)->toBeTrue()
        ->and($updated->updated_by)->toBe($user->id);
});

test('DeleteSubtask action deletes subtask', function () {
    $subtask = Subtask::factory()->create();

    $action = new DeleteSubtask();
    $action->handle($subtask);

    expect(Subtask::find($subtask->id))->toBeNull();
});

test('CompleteBoardCard action blocks completion when subtasks are incomplete', function () {
    $card = BoardCard::factory()->create();
    Subtask::factory()->create([
        'board_card_id' => $card->id,
        'is_completed' => false,
    ]);

    $action = new CompleteBoardCard();

    expect(fn () => $action->handle($card))
        ->toThrow(Exception::class, 'Cannot complete card until all subtasks are finished.');
});

test('CompleteBoardCard action completes card when all subtasks are done or no subtasks exist', function () {
    $card = BoardCard::factory()->create();
    Subtask::factory()->create([
        'board_card_id' => $card->id,
        'is_completed' => true,
    ]);

    $action = new CompleteBoardCard();
    $completedCard = $action->handle($card);

    expect($completedCard)->not->toBeNull();
});
