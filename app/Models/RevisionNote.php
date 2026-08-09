<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevisionNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'dosen_id',
        'catatan_revisi',
        'status_poin',
    ];

    protected $casts = [
        'status_poin' => 'string',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RevisionAttachment::class, 'revision_note_id');
    }

    public function isOpen(): bool
    {
        return $this->status_poin === 'open';
    }
}
