<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LabelController extends Controller
{
    public function index(Request $request, Team $currentTeam): InertiaResponse | RedirectResponse
    {
        $labels = $currentTeam->labels()
            ->withCount('cards')
            ->latest()
            ->get();

        // return Inertia::render('labels/Index', [
        //     'labels' => $labels,
        // ]);
        return to_route('board.index');
    }

    public function store(Request $request, Team $currentTeam): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:7'],
        ]);

        $currentTeam->labels()->create($validated);

        return to_route('labels.index', $currentTeam);
    }

    public function update(Request $request, Team $currentTeam, Label $label): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:7'],
        ]);

        $label->update($validated);

        return to_route('labels.index', $currentTeam);
    }

    public function destroy(Team $currentTeam, Label $label): RedirectResponse
    {
        $label->delete();

        return to_route('labels.index', $currentTeam);
    }
}