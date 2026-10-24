<?php

use App\Http\Controllers\CalendarEventController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::patch('calendar/{calendar}/reschedule', [CalendarEventController::class, 'reschedule'])->name('calendar.reschedule');
    	Route::resource('calendar', CalendarEventController::class);
    });
