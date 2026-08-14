<?php

use App\Models\RevisionAttachment;
use App\Models\RevisionNote;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('dosen dapat membuat catatan revisi pada submission', function () {
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $submission = Submission::factory()->create(['schedule_id' => $schedule->id]);

    $response = $this->actingAs($dosen)->post(route('dosen.revision-notes.store', $submission), [
        'catatan_revisi' => 'Perbaiki bagian analisis.',
    ]);

    $response->assertRedirect(route('dosen.submissions.show', $submission));

    $this->assertDatabaseHas('revision_notes', [
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'catatan_revisi' => 'Perbaiki bagian analisis.',
        'status_poin' => 'open',
    ]);

    $this->assertDatabaseHas('submissions', [
        'id' => $submission->id,
        'status' => 'revisi',
    ]);
});

test('dosen yang tidak ditugaskan tidak bisa memberi revisi', function () {
    $dosen = User::factory()->dosen()->create();
    $submission = Submission::factory()->create();

    $response = $this->actingAs($dosen)->post(route('dosen.revision-notes.store', $submission), [
        'catatan_revisi' => 'Catatan tidak sah.',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('revision_notes', 0);
});

test('mahasiswa dapat membalas poin revisi dengan teks dan lampiran', function () {
    Storage::fake('local');

    $mahasiswa = User::factory()->mahasiswa()->create();
    $submission = Submission::factory()->create(['user_id' => $mahasiswa->id]);
    $note = RevisionNote::factory()->create(['submission_id' => $submission->id]);

    $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

    $response = $this->actingAs($mahasiswa)->post(route('mahasiswa.revision-attachments.store', $note), [
        'keterangan_mahasiswa' => 'Sudah diperbaiki pada bab 3.',
        'file' => $file,
    ]);

    $response->assertRedirect(route('mahasiswa.submissions.show', $submission));

    $this->assertDatabaseHas('revision_attachments', [
        'revision_note_id' => $note->id,
        'keterangan_mahasiswa' => 'Sudah diperbaiki pada bab 3.',
    ]);

    Storage::disk('local')->assertExists(RevisionAttachment::first()->file_path);
});

test('mahasiswa hanya bisa membalas poin revisi pada submission miliknya', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $submission = Submission::factory()->create();
    $note = RevisionNote::factory()->create(['submission_id' => $submission->id]);

    $response = $this->actingAs($mahasiswa)->post(route('mahasiswa.revision-attachments.store', $note), [
        'keterangan_mahasiswa' => 'Akses tidak sah.',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('revision_attachments', 0);
});

test('dosen dapat menandai poin revisi sebagai resolved', function () {
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $submission = Submission::factory()->create(['schedule_id' => $schedule->id]);
    $note = RevisionNote::factory()->create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'status_poin' => 'open',
    ]);

    $response = $this->actingAs($dosen)->patch(route('dosen.revision-notes.resolve', $note), [
        'status_poin' => 'resolved',
    ]);

    $response->assertRedirect(route('dosen.submissions.show', $submission));

    $this->assertDatabaseHas('revision_notes', [
        'id' => $note->id,
        'status_poin' => 'resolved',
    ]);

    $this->assertDatabaseHas('submissions', [
        'id' => $submission->id,
        'status' => 'selesai',
    ]);
});

test('dosen hanya bisa resolve poin revisi miliknya', function () {
    $dosen = User::factory()->dosen()->create();
    $dosenLain = User::factory()->dosen()->create();
    $submission = Submission::factory()->create();
    $note = RevisionNote::factory()->create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosenLain->id,
    ]);

    $response = $this->actingAs($dosen)->patch(route('dosen.revision-notes.resolve', $note), [
        'status_poin' => 'resolved',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('revision_notes', [
        'id' => $note->id,
        'status_poin' => 'open',
    ]);
});

test('dosen melihat tombol resolve hanya untuk poin revisi miliknya', function () {
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $submission = Submission::factory()->create(['schedule_id' => $schedule->id]);
    $note = RevisionNote::factory()->create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'catatan_revisi' => 'Poin milik saya.',
        'status_poin' => 'open',
    ]);

    $response = $this->actingAs($dosen)->get(route('dosen.submissions.show', $submission));

    $response->assertOk();
    $response->assertSee('Tandai Resolved');
    $response->assertSee('Poin Anda');
});

test('dosen tidak melihat tombol resolve untuk poin revisi dosen lain, tapi tetap bisa lihat statusnya', function () {
    $dosen = User::factory()->dosen()->create();
    $dosenLain = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $submission = Submission::factory()->create(['schedule_id' => $schedule->id]);
    $note = RevisionNote::factory()->create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosenLain->id,
        'catatan_revisi' => 'Poin dari dosen lain.',
        'status_poin' => 'open',
    ]);

    $response = $this->actingAs($dosen)->get(route('dosen.submissions.show', $submission));

    $response->assertOk();
    $response->assertSee('Poin dari dosen lain.');
    $response->assertSee('Open');
    $response->assertSee('Menunggu konfirmasi '.$dosenLain->name);
    $response->assertDontSee('Tandai Resolved');
});
