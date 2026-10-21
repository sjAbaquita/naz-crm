<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardCardController extends Controller
{
    public function store(Request $request, Team $currentTeam): RedirectResponse
    {
        $data = $request->validate([
            'column_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $column = $this->column($currentTeam, $data['column_id']);

        // Validate that assigned_to is a member of the current team
        if (! empty($data['assigned_to']) && ! $this->isTeamMember($currentTeam, $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'The selected user is not a member of this team.']);
        }

        BoardCard::create([
            ...$data,
            'column_id' => $column->id,
            'position' => $column->cards()->max('position') + 1,
        ]);

        return to_route('board.index', $currentTeam);
    }

    public function move(Request $request, Team $currentTeam, int $id): RedirectResponse
    {
        $data = $request->validate([
            'column_id' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $card = $this->card($currentTeam, $id);
        $target = $this->column($currentTeam, $data['column_id']);
        $newPosition = $data['position'];

        DB::transaction(function () use ($card, $target, $newPosition): void {
            $sourceColumnId = $card->column_id;

            // If moving to a different column, first shift target column cards to make room
            if ($sourceColumnId !== $target->id) {
                $target->cards()
                    ->where('position', '>=', $newPosition)
                    ->increment('position');
            } else {
                // Moving within same column - handle direction
                $oldPosition = $card->position;
                if ($newPosition < $oldPosition) {
                    // Moving up - shift cards between new and old position down
                    $target->cards()
                        ->where('position', '>=', $newPosition)
                        ->where('position', '<', $oldPosition)
                        ->increment('position');
                } elseif ($newPosition > $oldPosition) {
                    // Moving down - shift cards between old and new position up
                    $target->cards()
                        ->where('position', '>', $oldPosition)
                        ->where('position', '<=', $newPosition)
                        ->decrement('position');
                }
            }

            // Update the moved card
            $card->update([
                'column_id' => $target->id,
                'position' => $newPosition,
            ]);

            // Re-index source column if different
            if ($sourceColumnId !== $target->id) {
                $sourceColumn = BoardColumn::find($sourceColumnId);
                if ($sourceColumn) {
                    foreach ($sourceColumn->cards()->orderBy('position')->get()->values() as $index => $item) {
                        $item->update(['position' => $index]);
                    }
                }
            }
        });

        return to_route('board.index', $currentTeam);
    }

    public function update(Request $request, Team $currentTeam, int $id): RedirectResponse
    {
        abort_unless($request->user()->teamRole($currentTeam)?->isAtLeast(TeamRole::Admin), 403);

        $card = $this->card($currentTeam, $id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // Validate that assigned_to is a member of the current team
        if (! empty($data['assigned_to']) && ! $this->isTeamMember($currentTeam, $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'The selected user is not a member of this team.']);
        }

        $card->update($data);

        return to_route('board.index', $currentTeam);
    }

    public function destroy(Request $request, Team $currentTeam, int $id): RedirectResponse
    {
        abort_unless($request->user()->teamRole($currentTeam)?->isAtLeast(TeamRole::Admin), 403);

        $this->card($currentTeam, $id)->delete();

        return to_route('board.index', $currentTeam);
    }

    private function column(Team $team, int $id): BoardColumn
    {
        return $team->board()->firstOrFail()->columns()->findOrFail($id);
    }

    private function card(Team $team, int $id): BoardCard
    {
        return BoardCard::query()
            ->whereKey($id)
            ->whereHas('column', fn ($q) => $q->where('board_id', $team->board()->value('id')))
            ->firstOrFail();
    }

    private function isTeamMember(Team $team, int $userId): bool
    {
        return $team->members()->where('user_id', $userId)->exists();
    }
}
