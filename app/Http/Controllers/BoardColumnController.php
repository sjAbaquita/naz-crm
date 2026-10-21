<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\BoardColumn;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoardColumnController extends Controller
{
    public function store(Request $request, Team $currentTeam): RedirectResponse
    {
        abort_unless($request->user()->teamRole($currentTeam)?->isAtLeast(TeamRole::Admin), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $board = $currentTeam->board()->firstOrFail();

        $board->columns()->create([
            'title' => $data['title'],
            'position' => $board->columns()->max('position') + 1,
        ]);

        return to_route('board.index', $currentTeam);
    }

    public function reorder(Request $request, Team $currentTeam): RedirectResponse
    {
        abort_unless($request->user()->teamRole($currentTeam)?->isAtLeast(TeamRole::Admin), 403);

        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['integer'],
        ]);

        $board = $currentTeam->board()->firstOrFail();

        foreach ($data['columns'] as $index => $columnId) {
            $board->columns()->where('id', $columnId)->update([
                'position' => $index,
            ]);
        }

        return to_route('board.index', $currentTeam);
    }

    public function destroy(Request $request, Team $currentTeam, int $id): RedirectResponse
    {
        abort_unless($request->user()->teamRole($currentTeam)?->isAtLeast(TeamRole::Admin), 403);

        $board = $currentTeam->board()->firstOrFail();
        $column = $board->columns()->findOrFail($id);

        $column->delete();

        return to_route('board.index', $currentTeam);
    }
}
