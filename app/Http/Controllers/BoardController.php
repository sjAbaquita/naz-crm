<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
	public function index(): Response
	{
		$board = Board::with([
			'columns',
			'columns.cards'
		])->first();

        return Inertia::render('boards/Index', [
            'board' => $board,
        ]);
	}

    public function show($id)
    {
        $board = Board::with([
            'columns.cards' => function ($q) {
                $q->orderBy('position');
            }
        ])->findOrFail($id);

        return response()->json($board);
    }

    public function store(Request $request)
    {
        $board = Board::create([
            'name' => $request->name,
        ]);

        return response()->json($board);
    }
}
