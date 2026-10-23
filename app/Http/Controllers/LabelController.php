<?php

namespace App\Http\Controllers;

use App\Actions\CreateLabel;
use App\Actions\DeleteLabel;
use App\Actions\UpdateLabel;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Models\Label;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LabelController extends Controller
{
    public function index(Request $request, Team $currentTeam): InertiaResponse
    {
        $labels = $currentTeam->labels()
            ->withCount('cards')
            ->latest()
            ->get();

        return Inertia::render('labels/Index', [
            'labels' => $labels,
        ]);
    }

    public function store(StoreLabelRequest $request, Team $currentTeam, CreateLabel $createLabel)
    {
        $createLabel->handle($currentTeam, $request->validated());

        return to_route('labels.index', $currentTeam);
    }

    public function update(UpdateLabelRequest $request, Team $currentTeam, Label $label, UpdateLabel $updateLabel)
    {
        $updateLabel->handle($label, $request->validated());

        return to_route('labels.index', $currentTeam);
    }

    public function destroy(Team $currentTeam, Label $label, DeleteLabel $deleteLabel)
    {
        $deleteLabel->handle($label);

        return to_route('labels.index', $currentTeam);
    }
}