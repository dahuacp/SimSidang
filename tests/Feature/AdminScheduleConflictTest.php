<?php

use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\User;

test('store jadwal dengan dosen bentrok ditolak', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $existing = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Pagi',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $existing->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Grup Baru',
        'ruangan' => 'Ruang X',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ]);

    $response->assertSessionHasErrors('dosens');
    expect(session('errors')->first('dosens'))->toContain('Grup Pagi');
    $this->assertDatabaseCount('schedules', 1);
});

test('store jadwal dengan dosen sama tapi tanggal berbeda diterima', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $existing = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $existing->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Grup Baru',
        'ruangan' => 'Ruang X',
        'tanggal_sidang' => '2026-08-21',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ])->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseCount('schedules', 2);
});

test('update menggeser jam hingga dosen bentrok jadwal lain ditolak', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
    ]);
    $target->dosens()->attach($dosen->id);
    $lain = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Lain',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:30',
        'jam_selesai' => '12:30',
    ]);
    $lain->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.schedules.update', $target), [
        'nama_grup_sidang' => $target->nama_grup_sidang,
        'ruangan' => $target->ruangan,
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ]);

    $response->assertSessionHasErrors('dosens');
    expect(session('errors')->first('dosens'))->toContain('Grup Lain');
    expect($target->fresh()->jam_mulai->format('H:i'))->toBe('08:00');
});

test('update yang hanya mengubah ruangan mengabaikan dirinya sendiri', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $target->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $this->actingAs($admin)->put(route('admin.schedules.update', $target), [
        'nama_grup_sidang' => $target->nama_grup_sidang,
        'ruangan' => 'Ruang Baru',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ])->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseHas('schedules', ['id' => $target->id, 'ruangan' => 'Ruang Baru']);
});

test('update menggeser jam hingga mahasiswa ter-plot bentrok ditolak', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '13:00',
        'jam_selesai' => '15:00',
    ]);
    $target->mahasiswas()->attach($mahasiswa->id);
    $lain = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Lain',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '14:00',
        'jam_selesai' => '16:00',
    ]);
    $lain->mahasiswas()->attach($mahasiswa->id);
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.schedules.update', $target), [
        'nama_grup_sidang' => $target->nama_grup_sidang,
        'ruangan' => $target->ruangan,
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '14:30',
        'jam_selesai' => '16:30',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [],
    ]);

    $response->assertSessionHasErrors('mahasiswas');
    expect(session('errors')->first('mahasiswas'))->toContain('Grup Lain');
    expect($target->fresh()->jam_mulai->format('H:i'))->toBe('13:00');
});

test('plot mahasiswa ke jadwal overlap dengan jadwal lain ditolak', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
    ]);
    $lain = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Lain',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $lain->mahasiswas()->attach($mahasiswa->id);

    $response = $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $target), [
        'user_id' => $mahasiswa->id,
    ]);

    $response->assertSessionHasErrors('user_id');
    expect(session('errors')->first('user_id'))->toContain('Grup Lain');
    $this->assertDatabaseMissing('schedule_mahasiswa', [
        'schedule_id' => $target->id,
        'user_id' => $mahasiswa->id,
    ]);
});

test('plot mahasiswa ke jadwal jam menyambung diterima', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '11:00',
        'jam_selesai' => '13:00',
    ]);
    $lain = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $lain->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $target), [
        'user_id' => $mahasiswa->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('schedule_mahasiswa', [
        'schedule_id' => $target->id,
        'user_id' => $mahasiswa->id,
    ]);
});
