<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttachmentController;


Route::prefix('{current_team}/cards/{card}/attachments')
	->middleware(['auth', 'verified'])
	->group(function () {
		Route::post('/', [AttachmentController::class, 'store'])->name('attachments.store');
		Route::get('{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
		Route::delete('{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
	});
