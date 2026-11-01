<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttachmentController;
use App\Http\Middleware\EnsureTeamMembership;

Route::prefix('{current_team}/cards/{card}/attachments')
	->middleware(['auth', 'verified', EnsureTeamMembership::class])
	->group(function () {
		Route::post('/', [AttachmentController::class, 'store'])->name('attachments.store');
		Route::get('{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
		Route::delete('{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
	});