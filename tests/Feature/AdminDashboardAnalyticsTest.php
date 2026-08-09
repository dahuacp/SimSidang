<?php

use App\Models\RevisionNote;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\SubmissionStatusLog;
use App\Models\User;

test('admin dashboard menampilkan data analitik', function () {
    $admin = User::factory()->admin()->create();

    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'revisi',
    ]);
    $dosen = User::factory()->dosen()->create();
    RevisionNote::factory()->create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'status_poin' => 'open',
    ]);
    SubmissionStatusLog::factory()->create([
        'submission_id' => $submission->id,
        'status_baru' => 'revisi',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertViewHas('submissionStatus');
    $response->assertViewHas('scheduleSubmissions');
    $response->assertViewHas('revisionStats');
    $response->assertViewHas('statusTrend');
});
