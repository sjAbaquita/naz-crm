<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = UploadedFile::fake()->image('attachment.jpg');
        $path = $file->store('attachments', 'public');

        return [
            'board_card_id' => $this->faker->randomNumber(),
            'filename'      => $file->getClientOriginalName(),
            'filepath'      => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => $this->faker->randomNumber(),
        ];
    }
}