<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Attachment;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

// Helper to create a team, user, and card
function createTeamUserAndCard(TeamRole $role = TeamRole::Member): array
{
    [$team, $user] = teamUser($role);
    $column = BoardColumn::factory()->create(['board_id' => $team->board->id]);
    $card = BoardCard::factory()->create(['column_id' => $column->id]);
    return [$team, $user, $card];
}

// Test for unauthenticated access
test('unauthenticated users cannot manage attachments', function () {
    [$team, $user, $card] = createTeamUserAndCard();
    $file = UploadedFile::fake()->image('test.jpg');

    $this->post(route('attachments.store', [$team, $card]), ['file' => $file])->assertStatus(302);
    $this->get(route('attachments.download', [$team, $card, 1]))->assertStatus(302);
    $this->delete(route('attachments.destroy', [$team, $card, 1]))->assertStatus(302);
});

// Test for storing a valid attachment
test('valid attachment can be stored by authorized user', function () {
    [$team, $user, $card] = createTeamUserAndCard();
    $file = UploadedFile::fake()->image('test.jpg');

    $this->actingAs($user)
        ->post(route('attachments.store', [$team, $card]), ['file' => $file])
        ->assertStatus(302);

    Storage::disk('public')->assertExists('attachments/' . $file->hashName());
    $this->assertDatabaseHas('attachments', [
        'board_card_id' => $card->id,
        'filename'      => 'test.jpg',
        'mime_type'     => 'image/jpeg',
        'size'          => $file->getSize(),
        'uploaded_by'   => $user->id,
    ]);
});

// Test for storing an attachment with invalid mime type
test('attachment with invalid mime type cannot be stored', function () {
    [$team, $user, $card] = createTeamUserAndCard();
    $file = UploadedFile::fake()->create('document.zip', 100, 'application/zip');

    $this->actingAs($user)
        ->post(route('attachments.store', [$team, $card]), ['file' => $file])
        ->assertSessionHasErrors(['file']);

    Storage::disk('public')->assertMissing('attachments/' . $file->hashName());
    $this->assertDatabaseMissing('attachments', [
        'filename' => 'document.zip',
    ]);
});

// Test for downloading an attachment
test('attachment can be downloaded by authorized user', function () {
    [$team, $user, $card] = createTeamUserAndCard();
    $attachment = Attachment::factory()->create([
        'board_card_id' => $card->id,
        'uploaded_by'   => $user->id,
        'filepath'      => Storage::disk('public')->putFile('attachments', UploadedFile::fake()->image('download.jpg')),
        'filename'      => 'download.jpg',
        'mime_type'     => 'image/jpeg',
        'size'          => 12345,
    ]);

    $response = $this->actingAs($user)
        ->get(route('attachments.download', [$team, $card, $attachment]));

    $response->assertOk();
    $this->assertNotEmpty($response->getContent());

    Storage::disk('public')->assertExists($attachment->filepath);
});

// Test for deleting an attachment
test('attachment can be deleted by authorized user', function () {
    [$team, $user, $card] = createTeamUserAndCard();
    $attachment = Attachment::factory()->create([
        'board_card_id' => $card->id,
        'uploaded_by'   => $user->id,
        'filepath'      => Storage::disk('public')->putFile('attachments', UploadedFile::fake()->image('delete.jpg')),
        'filename'      => 'delete.jpg',
        'mime_type'     => 'image/jpeg',
        'size'          => 12345,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('attachments.destroy', [$team, $card, $attachment]));

    $response->assertStatus(204);

    Storage::disk('public')->assertMissing($attachment->filepath);
    $this->assertDatabaseMissing('attachments', [
        'id' => $attachment->id,
    ]);
});