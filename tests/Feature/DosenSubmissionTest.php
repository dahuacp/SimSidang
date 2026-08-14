<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

test('dosen melihat semua jadwal yang ditugaskan, bukan hanya hari ini', function () {
    $dosen = User::factory()->dosen()->create();
    $hariIni = Schedule::factory()->create(['tanggal_sidang' => now()->toDateString()]);
    $besok = Schedule::factory()->create(['tanggal_sidang' => now()->addDay()->toDateString()]);
    $hariIni->dosens()->attach($dosen->id);
    $besok->dosens()->attach($dosen->id);

    $response = $this->actingAs($dosen)->get(route('dosen.submissions.index'));

    $response->assertOk();
    $response->assertSee($hariIni->nama_grup_sidang);
    $response->assertSee($besok->nama_grup_sidang);
});

test('dosen tidak melihat jadwal yang bukan tugasnya', function () {
    $dosen = User::factory()->dosen()->create();
    $jadwalLain = Schedule::factory()->create();

    $this->actingAs($dosen)->get(route('dosen.submissions.index'))
        ->assertOk()
        ->assertDontSee($jadwalLain->nama_grup_sidang);
});

test('dosen melihat mahasiswa ter-plot tanpa submission', function () {
    $dosen = User::factory()->dosen()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($dosen)->get(route('dosen.submissions.index'))
        ->assertOk()
        ->assertSee($mahasiswa->name)
        ->assertSee('Belum upload');
});

test('dosen melihat submission mahasiswa di jadwalnya', function () {
    $dosen = User::factory()->dosen()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Laporan Khusus Dosen',
    ]);

    $this->actingAs($dosen)->get(route('dosen.submissions.index'))
        ->assertOk()
        ->assertSee($mahasiswa->name)
        ->assertSee('Laporan Khusus Dosen');
});

test('filter hari ini hanya menampilkan jadwal hari ini', function () {
    $dosen = User::factory()->dosen()->create();
    $hariIni = Schedule::factory()->create(['tanggal_sidang' => now()->toDateString()]);
    $besok = Schedule::factory()->create(['tanggal_sidang' => now()->addDay()->toDateString()]);
    $hariIni->dosens()->attach($dosen->id);
    $besok->dosens()->attach($dosen->id);

    $this->actingAs($dosen)->get(route('dosen.submissions.index', ['filter' => 'hari_ini']))
        ->assertOk()
        ->assertSee($hariIni->nama_grup_sidang)
        ->assertDontSee($besok->nama_grup_sidang);
});

test('halaman jadwal dosen menampilkan input pencarian mahasiswa', function () {
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);

    $this->actingAs($dosen)->get(route('dosen.submissions.index'))
        ->assertOk()
        ->assertSee('Cari Mahasiswa');
});
