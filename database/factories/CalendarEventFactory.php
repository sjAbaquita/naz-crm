<?php

namespace Database\Factories;

use App\Enums\EventLevel;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'owner_id' => User::factory(),
            'description' => fake()->optional()->paragraph(),
            'event_level' => fake()->randomElement(EventLevel::cases()),
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
