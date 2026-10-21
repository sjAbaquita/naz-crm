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
        ]);

        $column = $this->column($currentTeam, $data['column_id']);

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

        DB::transaction(function () use ($card, $target, $data): void {
            $sourceColumnId = $card->column_id;

            $card->update([
                'column_id' => $target->id,
                'position' => $data['position'],
            ]);

            foreach ($target->cards()->orderBy('position')->get()->values() as $index => $item) {
                $item->update(['position' => $index]);
            }

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
        ]);

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
}
