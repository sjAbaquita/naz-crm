<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
		$user = User::factory()->create();
		
        CalendarEvent::factory()
            ->count(5)
            ->create([
				'owner_id' => $user->id
			]);
    }
}
