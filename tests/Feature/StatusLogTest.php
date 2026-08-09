<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

test('observer mencatat log ketika status submission berubah', function () {
    $mhs = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $submission = Submission::factory()->create(['user_id' => $mhs->id, 'schedule_id' => $schedule->id, 'status' => 'pending']);

    $submission->update(['status' => 'revisi']);

    $this->assertDatabaseHas('submission_status_logs', [
        'submission_id' => $submission->id,
        'status_lama' => 'pending',
        'status_baru' => 'revisi',
        'diubah_oleh' => null,
    ]);
});

test('observer mencatat diubah_oleh saat status berubah oleh admin', function () {
    $admin = User::factory()->admin()->create();
    $mhs = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $submission = Submission::factory()->create(['user_id' => $mhs->id, 'schedule_id' => $schedule->id, 'status' => 'pending']);

    Submission::withoutEvents(function () use ($submission) {
        $submission->setAttribute('status', 'pending')->save();
    });

    $this->actingAs($admin);
    $submission->update(['status' => 'selesai']);

    $this->assertDatabaseHas('submission_status_logs', [
        'submission_id' => $submission->id,
        'status_lama' => 'pending',
        'status_baru' => 'selesai',
        'diubah_oleh' => $admin->id,
    ]);
});

test('history status tampil di halaman detail submission', function () {
    $admin = User::factory()->admin()->create();
    $mhs = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $submission = Submission::factory()->create(['user_id' => $mhs->id, 'schedule_id' => $schedule->id, 'judul_laporan' => 'Judul Log', 'status' => 'pending']);

    $submission->update(['status' => 'revisi']);
    $submission->update(['status' => 'selesai']);

    $response = $this->actingAs($admin)->get(route('admin.submissions.show', $submission));

    $response->assertOk();
    $response->assertSee('Pending');
    $response->assertSee('Revisi');
    $response->assertSee('Selesai');
});
