<?php

use App\Http\Controllers\CalendarController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
    	Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    });