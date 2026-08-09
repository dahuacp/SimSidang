<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'judul_laporan',
        'file_path',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function revisionNotes(): HasMany
    {
        return $this->hasMany(RevisionNote::class, 'submission_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SubmissionStatusLog::class, 'submission_id')->latest();
    }

    public function hasOpenRevisions(): bool
    {
        return $this->revisionNotes()->where('status_poin', 'open')->exists();
    }
}
