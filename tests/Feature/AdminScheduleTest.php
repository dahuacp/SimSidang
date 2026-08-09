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
