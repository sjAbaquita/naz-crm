<?php

use App\Http\Controllers\BoardCardController;
use App\Http\Controllers\BoardColumnController;
use App\Http\Controllers\BoardController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
    	Route::get('board', [BoardController::class, 'index'])->name('board.index');
		// Route::get('/board/{board}', [BoardController::class, 'show'])->name('board.show');
		// Route::post('/board', [BoardController::class, 'store'])->name('board.store');

		Route::post('/columns', [BoardColumnController::class, 'store'])->name('column.store');
		Route::post('/columns/reorder', [BoardColumnController::class, 'reorder'])->name('column.reorder');
		Route::delete('/columns/{id}', [BoardColumnController::class, 'destroy'])->name('column.destroy');

		Route::post('/cards', [BoardCardController::class, 'store'])->name('cards.store');
		Route::patch('/cards/{id}/move', [BoardCardController::class, 'move'])->name('cards.move');
		Route::put('/cards/{id}', [BoardCardController::class, 'update'])->name('cards.update');
		Route::delete('/cards/{id}', [BoardCardController::class, 'destroy'])->name('cards.destroy');
    });
