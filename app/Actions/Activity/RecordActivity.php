<?php

namespace App\Actions\Activity;

use App\Models\Activity;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RecordActivity
{
    /**
     * Record a team activity.
     */
    public function handle(
        Team|int|null $team,
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $properties = null,
        ?int $userId = null
    ): ?Activity {
        $teamId = $team instanceof Team ? $team->id : $team;
        if (!$teamId) {
            return null;
        }
        $userId = $userId ?? Auth::id();

        return Activity::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}