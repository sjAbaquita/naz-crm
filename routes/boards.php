<?php

use App\Http\Controllers\BoardCardDependencyController;
use App\Http\Controllers\BoardCardController;
use App\Http\Controllers\BoardColumnController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('board', [BoardController::class, 'index'])->name('board.index');

        Route::post('/columns', [BoardColumnController::class, 'store'])->name('column.store');
        Route::post('/columns/reorder', [BoardColumnController::class, 'reorder'])->name('column.reorder');
        Route::delete('/columns/{id}', [BoardColumnController::class, 'destroy'])->name('column.destroy');

        Route::post('/cards', [BoardCardController::class, 'store'])->name('cards.store');
        Route::patch('/cards/{id}/move', [BoardCardController::class, 'move'])->name('cards.move');
        Route::put('/cards/{id}', [BoardCardController::class, 'update'])->name('cards.update');
        Route::delete('/cards/{id}', [BoardCardController::class, 'destroy'])->name('cards.destroy');

        // Subtask routes
        Route::get('/cards/{card}/subtasks', [SubtaskController::class, 'index'])->name('subtasks.index');
        Route::post('/cards/{card}/subtasks', [SubtaskController::class, 'store'])->name('subtasks.store');
        Route::put('/cards/{card}/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('subtasks.update');
        Route::delete('/cards/{card}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('subtasks.destroy');

        // Task detail page
        Route::get('/cards/{card}', [TaskController::class, 'show'])->name('tasks.show');

        // Dependency routes
        Route::post('/cards/{card}/dependencies', [BoardCardDependencyController::class, 'store'])->name('cards.dependencies.store');

        Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
        Route::post('/labels', [LabelController::class, 'store'])->name('labels.store');
        Route::put('/labels/{label}', [LabelController::class, 'update'])->name('labels.update');
        Route::delete('/labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
    });
