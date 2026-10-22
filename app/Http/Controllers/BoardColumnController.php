<?php

namespace App\Http\Controllers;

use App\Actions\Board\CreateBoardColumn;
use App\Actions\Board\ReorderBoardColumns;
use App\Actions\Board\UpdateBoardColumn;
use App\Http\Requests\Board\ReorderBoardColumnsRequest;
use App\Http\Requests\Board\StoreBoardColumnRequest;
use App\Http\Requests\Board\UpdateBoardColumnRequest;
use App\Models\BoardColumn;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BoardColumnController extends Controller
{
    /**
     * Store a newly created board column.
     */
    public function store(StoreBoardColumnRequest $request, Team $currentTeam, CreateBoardColumn $createBoardColumn): RedirectResponse
    {
        Gate::authorize('create', [BoardColumn::class, $currentTeam]);

        $board = $currentTeam->board()->firstOrFail();
        $createBoardColumn->handle($board->id, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column created.')]);

        return to_route('board.index', $currentTeam);
    }

    /**
     * Update the specified board column.
     */
    public function update(UpdateBoardColumnRequest $request, Team $currentTeam, int $id): RedirectResponse
    {
        $column = $this->column($currentTeam, $id);
        Gate::authorize('update', $column);

        $updateBoardColumn = app(UpdateBoardColumn::class);
        $updateBoardColumn->handle($column, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column updated.')]);

        return to_route('board.index', $currentTeam);
    }

    /**
     * Reorder board columns.
     */
    public function reorder(ReorderBoardColumnsRequest $request, Team $currentTeam, ReorderBoardColumns $reorderBoardColumns): RedirectResponse
    {
        Gate::authorize('reorder', [BoardColumn::class, $currentTeam]);

        $board = $currentTeam->board()->firstOrFail();
        $reorderBoardColumns->handle($board, $request->validated('columns'));

        return to_route('board.index', $currentTeam);
    }

    /**
     * Remove the specified board column.
     */
    public function destroy(Team $currentTeam, int $id): RedirectResponse
    {
        $column = $this->column($currentTeam, $id);
        Gate::authorize('delete', $column);

        $column->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column deleted.')]);

        return to_route('board.index', $currentTeam);
    }

    private function column(Team $team, int $id): BoardColumn
    {
        return $team->board()->firstOrFail()->columns()->findOrFail($id);
    }
}
