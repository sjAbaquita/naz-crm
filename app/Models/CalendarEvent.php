<?php

namespace App\Models;

use App\Enums\EventLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
