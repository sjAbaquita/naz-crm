<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
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
