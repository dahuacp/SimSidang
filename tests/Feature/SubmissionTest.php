<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('mahasiswa dapat mengunggah laporan PDF', function () {
    Storage::fake('local');

    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $file = UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf');

    $response = $this->actingAs($mahasiswa)->post('/mahasiswa/submissions', [
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Laporan Tugas Akhir',
        'file' => $file,
    ]);

    $response->assertRedirect(route('mahasiswa.submissions.index'));

    $this->assertDatabaseHas('submissions', [
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Laporan Tugas Akhir',
        'status' => 'pending',
    ]);

    Storage::disk('local')->assertExists(Submission::first()->file_path);
});

test('upload laporan menolak file non-PDF', function () {
    Storage::fake('local');

    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $file = UploadedFile::fake()->create('laporan.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $response = $this->actingAs($mahasiswa)->post('/mahasiswa/submissions', [
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Laporan Tugas Akhir',
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
    $this->assertDatabaseCount('submissions', 0);
});

test('upload laporan menolak file lebih dari 10MB', function () {
    Storage::fake('local');

    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $file = UploadedFile::fake()->create('laporan.pdf', 11000, 'application/pdf');

    $response = $this->actingAs($mahasiswa)->post('/mahasiswa/submissions', [
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Laporan Tugas Akhir',
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
    $this->assertDatabaseCount('submissions', 0);
});

test('mahasiswa hanya melihat submission miliknya', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $mahasiswaLain = User::factory()->mahasiswa()->create();

    Submission::factory()->create(['user_id' => $mahasiswa->id]);
    Submission::factory()->create(['user_id' => $mahasiswaLain->id]);

    $response = $this->actingAs($mahasiswa)->get(route('mahasiswa.submissions.index'));

    $response->assertOk();
    $response->assertSee(Submission::where('user_id', $mahasiswa->id)->first()->judul_laporan);
    $response->assertDontSee(Submission::where('user_id', $mahasiswaLain->id)->first()->judul_laporan);
});
