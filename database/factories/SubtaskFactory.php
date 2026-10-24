<?php

namespace Database\Factories;

use App\Models\BoardCard;
use App\Models\Subtask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subtask>
 */
class SubtaskFactory extends Factory
{
    protected $model = Subtask::class;

    public function definition(): array
    {
        return [
            'board_card_id' => BoardCard::factory(),
            'title' => fake()->sentence(),
            'is_completed' => fake()->boolean(),
            'position' => fake()->numberBetween(0, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}