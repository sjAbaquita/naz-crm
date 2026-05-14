<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    //
	public function index(Request $request): Response
	{
        $user = $request->user();

        return Inertia::render('calendar/Index', [
            'events' => $user->calendarEvents,
        ]);
	}
}
