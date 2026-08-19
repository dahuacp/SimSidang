<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** @extends HasFactory<User> */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'prodi_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function username(): string
    {
        return 'username';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDosen(): bool
    {
        return $this->role === 'dosen';
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'user_id');
    }

    public function latestSubmission(): HasOne
    {
        return $this->hasOne(Submission::class, 'user_id')->latestOfMany();
    }

    public function schedulesAsDosen(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'schedule_dosen', 'user_id', 'schedule_id');
    }

    public function schedulesAsPlot(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'schedule_mahasiswa', 'user_id', 'schedule_id');
    }

    public function dosenPembimbing(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pembimbingan', 'mahasiswa_id', 'dosen_id');
    }

    public function dosenPembimbingByUrutan(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pembimbingan', 'mahasiswa_id', 'dosen_id')
            ->withPivot('urutan')
            ->orderBy('urutan');
    }

    public function mahasiswaBimbingan(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pembimbingan', 'dosen_id', 'mahasiswa_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }
}
