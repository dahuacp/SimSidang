<?php

use App\Http\Controllers\Admin\AssessmentTemplateController;
use App\Http\Controllers\Admin\AssistantController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FakultasController;
use App\Http\Controllers\Admin\JenisSidangController;
use App\Http\Controllers\Admin\PenilaianController as AdminPenilaianController;
use App\Http\Controllers\Admin\ProdiController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dosen\AiSubmissionController;
use App\Http\Controllers\Dosen\PenilaianController as DosenPenilaianController;
use App\Http\Controllers\Dosen\RevisionNoteController as DosenRevisionNoteController;
use App\Http\Controllers\Dosen\SubmissionController as DosenSubmissionController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Mahasiswa\PenilaianController as MahasiswaPenilaianController;
use App\Http\Controllers\Mahasiswa\RevisionAttachmentController;
use App\Http\Controllers\Mahasiswa\SubmissionController as MahasiswaSubmissionController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/files/submissions/{submission}', [FileController::class, 'submission'])->name('files.submission');
    Route::get('/files/attachments/{attachment}', [FileController::class, 'attachment'])->name('files.attachment');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::middleware(['role:mahasiswa'])
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {
            Route::get('/submissions', [MahasiswaSubmissionController::class, 'index'])->name('submissions.index');
            Route::get('/submissions/create', [MahasiswaSubmissionController::class, 'create'])->name('submissions.create');
            Route::post('/submissions', [MahasiswaSubmissionController::class, 'store'])->name('submissions.store');
            Route::get('/submissions/{submission}', [MahasiswaSubmissionController::class, 'show'])->name('submissions.show');
            Route::get('/submissions/{submission}/penilaian', [MahasiswaPenilaianController::class, 'show'])->name('penilaian.show');
            Route::get('/submissions/{submission}/penilaian/{assessmentForm}/cetak', [MahasiswaPenilaianController::class, 'cetak'])->name('penilaian.cetak');
            Route::post('/revision-notes/{revisionNote}/attachments', [RevisionAttachmentController::class, 'store'])->name('revision-attachments.store');
        });

    Route::middleware(['role:dosen'])
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {
            Route::get('/submissions', [DosenSubmissionController::class, 'index'])->name('submissions.index');
            Route::get('/submissions/{submission}', [DosenSubmissionController::class, 'show'])->name('submissions.show');
            Route::post('/submissions/{submission}/ai-read', [AiSubmissionController::class, 'analyze'])->name('ai-read');
            Route::post('/submissions/{submission}/ai-read/refresh', [AiSubmissionController::class, 'refresh'])->name('ai-read.refresh');
            Route::get('/submissions/{submission}/revision-notes/create', [DosenRevisionNoteController::class, 'create'])->name('revision-notes.create');
            Route::post('/submissions/{submission}/revision-notes', [DosenRevisionNoteController::class, 'store'])->name('revision-notes.store');
            Route::post('/submissions/{submission}/revision-notes/draft', [DosenRevisionNoteController::class, 'draft'])->name('revision-notes.draft');
            Route::patch('/revision-notes/{revisionNote}/resolve', [DosenRevisionNoteController::class, 'resolve'])->name('revision-notes.resolve');

            Route::get('/penilaian', [DosenPenilaianController::class, 'index'])->name('penilaian.index');
            Route::get('/submissions/{submission}/penilaian', [DosenPenilaianController::class, 'create'])->name('penilaian.create');
            Route::post('/submissions/{submission}/penilaian', [DosenPenilaianController::class, 'store'])->name('penilaian.store');
            Route::get('/penilaian/{assessmentForm}/edit', [DosenPenilaianController::class, 'edit'])->name('penilaian.edit');
            Route::put('/penilaian/{assessmentForm}', [DosenPenilaianController::class, 'update'])->name('penilaian.update');
            Route::get('/penilaian/{assessmentForm}/cetak', [DosenPenilaianController::class, 'cetak'])->name('penilaian.cetak');
        });

    Route::middleware(['role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
            Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
            Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
            Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
            Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
            Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
            Route::post('/schedules/import', [ScheduleController::class, 'import'])->name('schedules.import');
            Route::get('/schedules/template', [ScheduleController::class, 'template'])->name('schedules.template');
            Route::get('/schedules/search-users', [ScheduleController::class, 'searchUsers'])->name('schedules.search-users');
            Route::get('/schedules/{schedule}/search-users', [ScheduleController::class, 'searchUsers']);
            Route::post('/schedules/{schedule}/mahasiswa', [ScheduleController::class, 'storeMahasiswa'])->name('schedules.mahasiswa.store');
            Route::delete('/schedules/{schedule}/mahasiswa/{user}', [ScheduleController::class, 'destroyMahasiswa'])->name('schedules.mahasiswa.destroy');

            Route::get('/submissions', [AdminSubmissionController::class, 'index'])->name('submissions.index');
            Route::get('/submissions/{submission}', [AdminSubmissionController::class, 'show'])->name('submissions.show');
            Route::get('/submissions/{submission}/penilaian/{assessmentForm}/cetak', [AdminPenilaianController::class, 'cetak'])->name('penilaian.cetak');
            Route::post('/submissions/{submission}/pembimbing', [AdminSubmissionController::class, 'storePembimbing'])->name('submissions.pembimbing.store');
            Route::delete('/submissions/{submission}/pembimbing/{dosen}', [AdminSubmissionController::class, 'destroyPembimbing'])->name('submissions.pembimbing.destroy');

            Route::view('/rekap', 'admin.rekap.index')->name('rekap');
            Route::get('/rekap/export-excel', [ExportController::class, 'excel'])->name('rekap.export-excel');
            Route::get('/rekap/export-pdf', [ExportController::class, 'pdf'])->name('rekap.export-pdf');
            Route::get('/rekap/cetak-penilaian', [AdminPenilaianController::class, 'cetakIndex'])->name('rekap.cetak-penilaian');

            // Asisten Virtual (FR-05)
            Route::get('/asisten', [AssistantController::class, 'index'])->name('assistant.index');
            Route::get('/asisten/new', [AssistantController::class, 'createNew'])->name('assistant.new');
            Route::get('/asisten/conversations', [AssistantController::class, 'conversations'])->name('assistant.conversations');
            Route::get('/asisten/{conversation}', [AssistantController::class, 'show'])->name('assistant.show');
            Route::post('/asisten/{conversation}/chat', [AssistantController::class, 'chat'])->name('assistant.chat')->middleware('throttle:assistant');

            // Prodi
            Route::get('/prodis', [ProdiController::class, 'index'])->name('prodis.index');
            Route::get('/prodis/create', [ProdiController::class, 'create'])->name('prodis.create');
            Route::post('/prodis', [ProdiController::class, 'store'])->name('prodis.store');
            Route::get('/prodis/{prodi}/edit', [ProdiController::class, 'edit'])->name('prodis.edit');
            Route::put('/prodis/{prodi}', [ProdiController::class, 'update'])->name('prodis.update');
            Route::delete('/prodis/{prodi}', [ProdiController::class, 'destroy'])->name('prodis.destroy');

            // Fakultas
            Route::get('/fakultas', [FakultasController::class, 'index'])->name('fakultas.index');
            Route::get('/fakultas/create', [FakultasController::class, 'create'])->name('fakultas.create');
            Route::post('/fakultas', [FakultasController::class, 'store'])->name('fakultas.store');
            Route::get('/fakultas/{fakultas}/edit', [FakultasController::class, 'edit'])->name('fakultas.edit');
            Route::put('/fakultas/{fakultas}', [FakultasController::class, 'update'])->name('fakultas.update');
            Route::delete('/fakultas/{fakultas}', [FakultasController::class, 'destroy'])->name('fakultas.destroy');

            Route::resource('jenis-sidangs', JenisSidangController::class)->only([
                'index', 'create', 'store', 'edit', 'update', 'destroy',
            ]);

            Route::resource('assessment-templates', AssessmentTemplateController::class)->only([
                'index', 'create', 'store', 'edit', 'update', 'destroy',
            ]);
        });
});
