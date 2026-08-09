<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('admin dapat export rekap ke Excel', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $mhs = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    Submission::factory()->create(['user_id' => $mhs->id, 'schedule_id' => $schedule->id, 'judul_laporan' => 'Laporan X']);

    $response = $this->actingAs($admin)->get(route('admin.rekap.export-excel'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('admin dapat export rekap ke PDF', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $mhs = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    Submission::factory()->create(['user_id' => $mhs->id, 'schedule_id' => $schedule->id, 'judul_laporan' => 'Laporan PDF']);

    $response = $this->actingAs($admin)->get(route('admin.rekap.export-pdf'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});
