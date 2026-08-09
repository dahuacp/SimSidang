<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\Tools\GetDosenWorkloadTool;
use App\Services\VirtualAssistant\Tools\GetScheduleSummaryTool;
use App\Services\VirtualAssistant\Tools\GetStalledRevisionsTool;
use App\Services\VirtualAssistant\Tools\GetStudentProgressTool;
use Illuminate\Support\Facades\DB;

test('getStudentProgress mengembalikan statistik submission per status', function () {
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'pending',
    ]);
    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'selesai',
    ]);

    $result = (new GetStudentProgressTool)->execute([]);

    expect($result['total_submission'])->toBe(2);
    expect($result['status_submission']['pending'])->toBe(1);
    expect($result['status_submission']['selesai'])->toBe(1);
    expect($result['status_submission']['revisi'])->toBe(0);
});

test('getStudentProgress dapat difilter per schedule', function () {
    $scheduleA = Schedule::factory()->create();
    $scheduleB = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $scheduleA->id,
        'status' => 'pending',
    ]);
    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $scheduleB->id,
        'status' => 'selesai',
    ]);

    $result = (new GetStudentProgressTool)->execute(['schedule_id' => $scheduleA->id]);

    expect($result['total_submission'])->toBe(1);
    expect($result['status_submission']['pending'])->toBe(1);
    expect($result['status_submission']['selesai'])->toBe(0);
});

test('getDosenWorkload mengembalikan beban kerja per dosen', function () {
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $mahasiswa = User::factory()->mahasiswa()->create();

    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'revisi',
    ]);

    DB::table('revision_notes')->insert([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'catatan_revisi' => 'Perbaiki metodologi',
        'status_poin' => 'open',
    ]);

    $result = (new GetDosenWorkloadTool)->execute([]);

    expect($result['total_dosen'])->toBe(1);
    expect($result['dosen_beban'][0]['nama_dosen'])->toBe($dosen->name);
    expect($result['dosen_beban'][0]['jumlah_jadwal'])->toBe(1);
    expect($result['dosen_beban'][0]['total_poin_revisi'])->toBe(1);
});

test('getStalledRevisions mengembalikan poin revisi yang terlalu lama dibuka', function () {
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $dosen = User::factory()->dosen()->create();
    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'revisi',
    ]);

    DB::table('revision_notes')->insert([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'catatan_revisi' => 'Perbaiki analisis data',
        'status_poin' => 'open',
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $result = (new GetStalledRevisionsTool)->execute(['hari' => 7]);

    expect($result['batas_hari'])->toBe(7);
    expect($result['total_poin_terjebak'])->toBe(1);
    expect($result['rata_rata_hari'])->toBe(10.0);
});

test('getScheduleSummary mengembalikan ringkasan jadwal', function () {
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    Submission::factory()->count(3)->create([
        'schedule_id' => $schedule->id,
        'status' => 'pending',
    ]);
    Submission::factory()->create([
        'schedule_id' => $schedule->id,
        'status' => 'selesai',
    ]);

    $result = (new GetScheduleSummaryTool)->execute([]);

    expect($result['total_jadwal'])->toBe(1);
    expect($result['total_submission'])->toBe(4);
    expect($result['distribusi_status']['pending'])->toBe(3);
    expect($result['distribusi_status']['selesai'])->toBe(1);
});
