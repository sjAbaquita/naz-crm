<?php

namespace App\Models;

use App\Enums\EventLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['owner_id', 'title', 'description', 'location', 'event_level', 'start_date', 'end_date'])]
class CalendarEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarEventFactory> */
    use HasFactory;

	protected function casts(): array
	{
		return [
			'event_level' => EventLevel::class,
		];
	}
}
