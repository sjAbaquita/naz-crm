<?php

namespace App\Http\Controllers;

use App\Actions\Board\CreateBoardCard;
use App\Actions\Board\MoveBoardCard;
use App\Actions\Board\UpdateBoardCard;
use App\Http\Requests\Board\MoveBoardCardRequest;
use App\Http\Requests\Board\StoreBoardCardRequest;
use App\Http\Requests\Board\UpdateBoardCardRequest;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BoardCardController extends Controller
{
    /**
     * Store a newly created board card.
     */
    public function store(StoreBoardCardRequest $request, Team $currentTeam, CreateBoardCard $createBoardCard): RedirectResponse
    {
        Gate::authorize('create', [BoardCard::class, $currentTeam]);

        $column = $this->column($currentTeam, $request->validated('column_id'));
        $card = $createBoardCard->handle($column->id, $request->validated(), $request->user()->id);

        // sync labels if provided
        if ($request->filled('labels')) {
            $card->labels()->sync($request->input('labels'));
        }

        return to_route('board.index', $currentTeam);
    }

    /**
     * Move a board card.
     */
    public function move(MoveBoardCardRequest $request, Team $currentTeam, int $id, MoveBoardCard $moveBoardCard): RedirectResponse
    {
        $card = $this->card($currentTeam, $id);
        Gate::authorize('move', $card);

        $target = $this->column($currentTeam, $request->validated('column_id'));
        $moveBoardCard->handle($card, $target, $request->validated('position'));

        return to_route('board.index', $currentTeam);
    }

    /**
     * Update the specified board card.
     */
    public function update(UpdateBoardCardRequest $request, Team $currentTeam, int $id): RedirectResponse
    {
        $card = $this->card($currentTeam, $id);
        Gate::authorize('update', $card);

        if ($request->has('is_completed') && $request->validated('is_completed')) {
            app(\App\Actions\Board\CompleteBoardCard::class)->handle($card);
        }

        $updateBoardCard = app(UpdateBoardCard::class);
        $updateBoardCard->handle($card, array_filter($request->validated(), fn($key) => $key !== 'is_completed', ARRAY_FILTER_USE_KEY));

        // sync labels if provided
        if ($request->filled('labels')) {
            $card->labels()->sync($request->input('labels'));
        }

        return to_route('board.index', $currentTeam);
    }

    /**
     * Remove the specified board card.
     */
    public function destroy(Team $currentTeam, int $id): RedirectResponse
    {
        $card = $this->card($currentTeam, $id);
        Gate::authorize('delete', $card);

        $card->delete();

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
