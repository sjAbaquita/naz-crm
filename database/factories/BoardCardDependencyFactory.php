<?php

namespace Database\Factories;

use App\Models\BoardCard;
use App\Models\BoardCardDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardCardDependency>
 */
class BoardCardDependencyFactory extends Factory
{
    protected $model = BoardCardDependency::class;

    public function definition(): array
    {
        return [
            'board_card_id' => BoardCard::factory(),
            'depends_on_card_id' => BoardCard::factory(),
        ];
    }
}