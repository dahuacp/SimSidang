<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'prodi_id',
        'jenis_sidang_id',
        'nama',
        'nilai_penyebut',
        'nilai_pengali',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'nilai_penyebut' => 'integer',
        'nilai_pengali' => 'integer',
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function jenisSidang(): BelongsTo
    {
        return $this->belongsTo(JenisSidang::class, 'jenis_sidang_id');
    }

    public function forms(): HasMany
    {
        return $this->hasMany(AssessmentForm::class, 'template_id');
    }

    public function calculateTotal(array $skorPerItem): float
    {
        $sum = 0;
        foreach ($this->items as $idx => $item) {
            $sum += (float) ($skorPerItem[$idx]['skor'] ?? 0);
        }

        $penyebut = max(1, $this->nilai_penyebut);

        return round($sum / $penyebut * $this->nilai_pengali, 1);
    }
}
