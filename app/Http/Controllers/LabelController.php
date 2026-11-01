<?php

namespace App\Http\Controllers;

use App\Actions\Activity\RecordActivity;
use App\Models\Label;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    public function index(Request $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('viewAny', [Label::class, $currentTeam]);

        return to_route('board.index', $currentTeam);
    }

    public function store(Request $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [Label::class, $currentTeam]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        $label = $currentTeam->labels()->create($validated);

        app(RecordActivity::class)->handle(
            team: $currentTeam,
            action: 'created_label',
            description: "created label '{$label->name}'",
            subject: $label,
        );

        return to_route('board.index', $currentTeam);
    }

    public function update(Request $request, Team $currentTeam, Label $label): RedirectResponse
    {
        Gate::authorize('update', [$label, $currentTeam]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        $label->update($validated);

        app(RecordActivity::class)->handle(
            team: $currentTeam,
            action: 'updated_label',
            description: "updated label '{$label->name}'",
            subject: $label,
        );

        return to_route('board.index', $currentTeam);
    }

    public function destroy(Team $currentTeam, Label $label): RedirectResponse
    {
        Gate::authorize('delete', [$label, $currentTeam]);

        app(RecordActivity::class)->handle(
            team: $currentTeam,
            action: 'deleted_label',
            description: "deleted label '{$label->name}'",
            subject: $label,
        );

        $label->delete();

        return to_route('board.index', $currentTeam);
    }
}