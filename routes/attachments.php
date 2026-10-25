<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttachmentController;

Route::middleware(['auth', 'team'])->prefix('{team}/cards/{card}/attachments')->group(function () {
    Route::post('/', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});
