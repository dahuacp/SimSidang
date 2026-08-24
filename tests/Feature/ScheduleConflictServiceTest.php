<?php

use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleConflictService;

test('menemukan konflik dosen pada rentang waktu overlap', function () {
    $dosen = User::factory()->dosen()->create();

    $pagi = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Pagi',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $pagi->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-20', '10:30', '12:00');

    expect($hasil)->toHaveCount(1)
        ->and($hasil[$dosen->id]['user']->id)->toBe($dosen->id)
        ->and($hasil[$dosen->id]['schedules']->pluck('id')->all())->toContain($pagi->id);
});

test('jam menyambung langsung tidak dianggap konflik', function () {
    $dosen = User::factory()->dosen()->create();

    $pagi = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $pagi->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-20', '11:00', '13:00');

    expect($hasil)->toHaveCount(0);
});

test('tanggal berbeda tidak dianggap konflik', function () {
    $dosen = User::factory()->dosen()->create();

    $jadwal = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $jadwal->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-21', '09:00', '11:00');

    expect($hasil)->toHaveCount(0);
});

test('ignoreScheduleId mengecualikan jadwal itu sendiri', function () {
    $dosen = User::factory()->dosen()->create();

    $jadwal = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $jadwal->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-20', '09:00', '11:00', $jadwal->id);

    expect($hasil)->toHaveCount(0);
});

test('menemukan konflik mahasiswa', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $jadwal = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $jadwal->mahasiswas()->attach($mahasiswa->id);

    $hasil = app(ScheduleConflictService::class)
        ->findMahasiswaConflicts([$mahasiswa->id], '2026-08-20', '10:00', '12:00');

    expect($hasil)->toHaveCount(1)
        ->and($hasil[$mahasiswa->id]['schedules'])->not->toBeEmpty();
});

test('daftar userId kosong mengembalikan koleksi kosong', function () {
    $hasil = app(ScheduleConflictService::class)->findDosenConflicts([], '2026-08-20', '09:00', '11:00');

    expect($hasil)->toHaveCount(0);
});
