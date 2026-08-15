<?php

use App\Models\JenisSidang;
use App\Models\Prodi;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\QueryException;

test('membuat jenis sidang dengan nama dan deskripsi', function () {
    $js = JenisSidang::create(['nama' => 'TA', 'deskripsi' => 'Tugas Akhir']);

    expect($js->nama)->toBe('TA');
    expect($js->deskripsi)->toBe('Tugas Akhir');
    $this->assertDatabaseHas('jenis_sidangs', ['nama' => 'TA']);
});

test('nama jenis sidang bersifat unik', function () {
    JenisSidang::create(['nama' => 'TA', 'deskripsi' => 'Tugas Akhir']);

    expect(fn () => JenisSidang::create(['nama' => 'TA', 'deskripsi' => 'Lainnya']))
        ->toThrow(QueryException::class);
});

test('schedule memiliki relasi belongsTo jenis_sidang', function () {
    $js = JenisSidang::create(['nama' => 'KP', 'deskripsi' => null]);
    $schedule = Schedule::create([
        'nama_grup_sidang' => 'Sidang KP Gel 1',
        'ruangan' => 'Ruang 1',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'jenis_sidang_id' => $js->id,
    ]);

    expect($schedule->jenisSidang->id)->toBe($js->id);
    expect($schedule->jenisSidang->nama)->toBe('KP');
});

test('mahasiswa dapat memiliki beberapa dosen pembimbing', function () {
    $prodi = Prodi::create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);
    $mhs = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);
    $dosen1 = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $dosen2 = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);

    $mhs->dosenPembimbing()->attach([$dosen1->id, $dosen2->id]);

    expect($mhs->dosenPembimbing)->toHaveCount(2);
    expect($mhs->fresh()->dosenPembimbing->pluck('id')->sort()->values()->toArray())
        ->toBe([$dosen1->id, $dosen2->id]);
});

test('dosen memiliki relasi mahasiswa bimbingan', function () {
    $prodi = Prodi::create(['kode_prodi' => 'TI', 'nama_prodi' => 'TI']);
    $dosen = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $mhs1 = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);
    $mhs2 = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);

    $dosen->mahasiswaBimbingan()->attach([$mhs1->id, $mhs2->id]);

    expect($dosen->mahasiswaBimbingan)->toHaveCount(2);
});

test('pivot pembimbingan menolak duplikat mahasiswa+dosen', function () {
    $prodi = Prodi::create(['kode_prodi' => 'TI', 'nama_prodi' => 'TI']);
    $mhs = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);
    $dosen = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);

    $mhs->dosenPembimbing()->attach($dosen->id);

    expect(fn () => $mhs->dosenPembimbing()->attach($dosen->id))
        ->toThrow(QueryException::class);
});

test('schedule jenis_sidang_id nullOnDelete aman saat jenis dihapus', function () {
    $js = JenisSidang::create(['nama' => 'TA', 'deskripsi' => null]);
    $schedule = Schedule::create([
        'nama_grup_sidang' => 'Sidang',
        'ruangan' => 'Ruang',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'jenis_sidang_id' => $js->id,
    ]);

    $js->delete();

    expect($schedule->fresh()->jenis_sidang_id)->toBeNull();
});

test('seeder DatabaseSeeder mengisi jenis_sidang dan pembimbingan', function () {
    $this->seed();

    expect(JenisSidang::count())->toBe(3);
    expect(JenisSidang::where('nama', 'TA')->exists())->toBeTrue();
    expect(JenisSidang::where('nama', 'KP')->exists())->toBeTrue();
    expect(JenisSidang::where('nama', 'Milestone Design')->exists())->toBeTrue();

    $schedule = Schedule::where('nama_grup_sidang', 'Sidang TA Gelombang 1')->first();
    expect($schedule->jenis_sidang_id)->not->toBeNull();
    expect($schedule->jenisSidang->nama)->toBe('TA');

    $kpSchedule = Schedule::where('nama_grup_sidang', 'Sidang KP Gelombang 1')->first();
    expect($kpSchedule->jenisSidang->nama)->toBe('KP');

    $mhs = User::where('role', 'mahasiswa')->get();
    expect($mhs->every(fn ($m) => $m->dosenPembimbing()->exists()))->toBeTrue();
});
