<?php

namespace App\Models;

use App\Enums\EventLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'owner_id', 'created_by', 'title', 'description', 'location', 'event_level', 'start_date', 'end_date'])]
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

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
