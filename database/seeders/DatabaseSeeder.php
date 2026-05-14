<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

		$board = Board::factory()->create([
			'name' => 'Default Board',
		]);

		$columns = collect([
			'Open',
			'In Progress',
			'For Verification',
			'Completed',
		]);

		$columns->each(function ($title, $index) use ($board) {

			$column = BoardColumn::factory()->create([
				'board_id' => $board->id,
				'title' => $title,
				'position' => $index,
			]);

			BoardCard::factory(3)->create([
				'column_id' => $column->id,
			]);
		});


		// calendar event seeder
		$this->call([
            CalendarEventSeeder::class,
        ]);
    }
}
