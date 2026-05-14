<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardColumn>
 */
class BoardColumnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Open',
            'In Progress',
            'For Verification',
            'Completed',
        ];

        return [
            'board_id' => Board::factory(),
            'title' => $this->faker->randomElement($titles),
            'position' => 0,
        ];
    }
}
