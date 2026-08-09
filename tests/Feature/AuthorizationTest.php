<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

test('dosen tidak bisa mengakses route mahasiswa', function () {
    $dosen = User::factory()->dosen()->create();

    $this->actingAs($dosen)
        ->get(route('mahasiswa.submissions.index'))
        ->assertForbidden();
});

test('mahasiswa tidak bisa mengakses route dosen', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $this->actingAs($mahasiswa)
        ->get(route('dosen.submissions.index'))
        ->assertForbidden();
});

test('user tamu diarahkan ke halaman login', function () {
    $this->get(route('mahasiswa.submissions.index'))->assertRedirect('/login');
});

test('mahasiswa tidak bisa melihat submission milik mahasiswa lain', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $submission = Submission::factory()->create();

    $this->actingAs($mahasiswa)
        ->get(route('mahasiswa.submissions.show', $submission))
        ->assertForbidden();
});

test('dosen yang tidak ditugaskan tidak bisa melihat submission', function () {
    $dosen = User::factory()->dosen()->create();
    $submission = Submission::factory()->create();

    $this->actingAs($dosen)
        ->get(route('dosen.submissions.show', $submission))
        ->assertForbidden();
});

test('dosen yang ditugaskan bisa melihat submission di grupnya', function () {
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $submission = Submission::factory()->create(['schedule_id' => $schedule->id]);

    $this->actingAs($dosen)
        ->get(route('dosen.submissions.show', $submission))
        ->assertOk();
});

test('admin dapat mengakses dashboard admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
