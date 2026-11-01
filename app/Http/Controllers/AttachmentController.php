<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\BoardCard;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Store a new attachment for a board card.
     */
    public function store(StoreAttachmentRequest $request, Team $currentTeam, BoardCard $card): JsonResponse
    {
        $file = $request->validated()['file'];
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'board_card_id' => $card->id,
            'filename'      => $file->getClientOriginalName(),
            'filepath'       => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => $request->user()->id,
        ]);

        // return response()->json(['attachment' => $attachment], 201);
				return response()->json(['attachment' => $attachment], 201);
		return response()->json(['attachment' => $attachment], 201);
    }

    /**
     * Download an attachment.
     */
    public function download(Request $request, Team $currentTeam, BoardCard $card, Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        return Storage::disk('public')->download($attachment->filepath, $attachment->filename);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Request $request, Team $currentTeam, BoardCard $card, Attachment $attachment): Response
    {
        Gate::authorize('delete', $attachment);

        Storage::disk('public')->delete($attachment->filepath);
        $attachment->delete();

        return response()->json(['attachment' => $attachment], 201);
    }
}
