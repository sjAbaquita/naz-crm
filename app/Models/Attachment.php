<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'board_card_id',
        'filename',
        'filepath',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function boardCard(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class);
    }
}
