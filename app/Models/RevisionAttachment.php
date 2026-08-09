<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'revision_note_id',
        'keterangan_mahasiswa',
        'file_path',
    ];

    public function revisionNote(): BelongsTo
    {
        return $this->belongsTo(RevisionNote::class, 'revision_note_id');
    }
}
