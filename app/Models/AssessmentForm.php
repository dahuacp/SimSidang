<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'dosen_id',
        'tipe_penilai',
        'template_id',
        'skor_per_item',
        'skor_total',
        'catatan',
    ];

    protected $casts = [
        'skor_per_item' => 'array',
        'skor_total' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $form): void {
            if ($form->template_id && is_array($form->skor_per_item)) {
                $form->skor_total = $form->template->calculateTotal($form->skor_per_item);
            }
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }
}
