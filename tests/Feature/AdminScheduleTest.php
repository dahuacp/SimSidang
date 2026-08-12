<?php

use App\Models\Schedule;
use App\Models\User;

test('admin dapat membuat jadwal dengan assign dosen', function () {
    $admin = User::factory()->admin()->create();
    $dosen1 = User::factory()->dosen()->create();
    $dosen2 = User::factory()->dosen()->create();

    $response = $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Sidang A',
        'ruangan' => 'Ruang 1',
        'tanggal_sidang' => '2026-08-15',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
        'dosens' => [$dosen1->id, $dosen2->id],
    ]);

    $response->assertRedirect(route('admin.schedules.index'));

    $schedule = Schedule::first();
    $this->assertDatabaseHas('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $dosen1->id,
    ]);
    $this->assertDatabaseHas('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $dosen2->id,
    ]);
});

test('admin dapat mengedit jadwal dan mengganti dosen', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $d1 = User::factory()->dosen()->create();
    $d2 = User::factory()->dosen()->create();

    $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), [
        'nama_grup_sidang' => 'Updated',
        'ruangan' => 'Ruang 2',
        'tanggal_sidang' => $schedule->tanggal_sidang->format('Y-m-d'),
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'dosens' => [$d2->id],
    ]);

    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'nama_grup_sidang' => 'Updated']);
    $this->assertDatabaseHas('schedule_dosen', ['schedule_id' => $schedule->id, 'user_id' => $d2->id]);
});

test('admin dapat menghapus jadwal', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();

    $this->actingAs($admin)->delete(route('admin.schedules.destroy', $schedule));

    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
});

test('admin dapat memplot mahasiswa ke jadwal', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $schedule), [
        'user_id' => $mahasiswa->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('schedule_mahasiswa', [
        'schedule_id' => $schedule->id,
        'user_id' => $mahasiswa->id,
    ]);
});

test('plot mahasiswa duplikat ditolak', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $schedule), [
        'user_id' => $mahasiswa->id,
    ])->assertRedirect();

    $this->from(route('admin.schedules.edit', $schedule))
        ->actingAs($admin)
        ->post(route('admin.schedules.mahasiswa.store', $schedule), [
            'user_id' => $mahasiswa->id,
        ])
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('schedule_mahasiswa', 1);
});

test('plot pengguna non-mahasiswa ditolak', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $dosen = User::factory()->dosen()->create();

    $this->from(route('admin.schedules.edit', $schedule))
        ->actingAs($admin)
        ->post(route('admin.schedules.mahasiswa.store', $schedule), [
            'user_id' => $dosen->id,
        ])
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('schedule_mahasiswa', 0);
});

test('admin dapat menghapus mahasiswa dari jadwal', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->delete(route('admin.schedules.mahasiswa.destroy', [$schedule, $mahasiswa]));

    $this->assertDatabaseMissing('schedule_mahasiswa', [
        'schedule_id' => $schedule->id,
        'user_id' => $mahasiswa->id,
    ]);
});

test('plot mahasiswa dihapus saat jadwal dihapus', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->delete(route('admin.schedules.destroy', $schedule));

    $this->assertDatabaseMissing('schedule_mahasiswa', ['schedule_id' => $schedule->id]);
});

test('halaman edit jadwal menampilkan mahasiswa yang sudah di-plot', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->get(route('admin.schedules.edit', $schedule))
        ->assertOk()
        ->assertSee('Plotting Mahasiswa')
        ->assertSee($mahasiswa->name)
        ->assertSee($mahasiswa->username);
});

test('halaman index jadwal menampilkan jumlah mahasiswa', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->get(route('admin.schedules.index'))
        ->assertOk()
        ->assertSee($schedule->nama_grup_sidang)
        ->assertSee('1');
});
