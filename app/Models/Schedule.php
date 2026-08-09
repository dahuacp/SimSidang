<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_grup_sidang',
        'ruangan',
        'tanggal_sidang',
        'jam_mulai',
        'jam_selesai',
    ];

    protected $casts = [
        'tanggal_sidang' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'schedule_id');
    }

    public function dosens(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'schedule_dosen', 'schedule_id', 'user_id');
    }
}
