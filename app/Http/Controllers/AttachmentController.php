<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\BoardCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Store a new attachment for a board card.
     */
    public function store(\App\Http\Requests\Attachment\StoreAttachmentRequest $request, string $team, int $cardId)
    {
        $card = BoardCard::findOrFail($cardId);

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

        return response()->json(['attachment' => $attachment], 201);
    }

    /**
     * Download an attachment.
     */
    public function download(string $team, int $cardId, int $attachmentId)
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $this->authorize('view', $attachment);

        return Storage::disk('public')->download($attachment->filepath, $attachment->filename);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(string $team, int $cardId, int $attachmentId)
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $this->authorize('delete', $attachment);

        Storage::disk('public')->delete($attachment->filepath);
        $attachment->delete();

        return response()->noContent();
    }
}
