<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

class AttachmentControllerTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function -non_member_cannot_upload_attachments()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }

    /**
     * @test
     * @return void
     */
    public function a_member_can_upload_attachments()
    {
        $this->markTestIncomplete('This test needs to be implemented.');
    }

    /**
     * @test
     * @return void
     */
    public function download_attachment()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        $response = $this->post('/attachments', ['file' => $file]);
        $attachmentId = json_decode($response->getContent(), true)['id'];

        // Act
        $response = $this->get('/attachments/' . $attachmentId . '/download');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    /**
     * @test
     * @return void
     */
    public function destroy_attachment()
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        $response = $this->post('/attachments', ['file' => $file]);
        $attachmentId = json_decode($response->getContent(), true)['id'];

        // Act
        $response = $this->delete('/attachments/' . $attachmentId);

        // Assert
        $response->assertStatus(200);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }
}
