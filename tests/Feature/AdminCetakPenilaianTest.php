<?php

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

function scenarioAdminCetak(): array
{
    $prodi = Prodi::factory()->create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);
    $jenis = JenisSidang::factory()->create(['nama' => 'Tugas Akhir']);

    $templatePenguji = AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'tipe_penilai' => 'penguji',
        'nama' => 'Template Penguji TI',
        'nilai_penyebut' => 12,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Penguasaan materi dan teori pendukung', 'maksimal' => 10, 'urutan' => 1],
            ['name' => 'Ketepatan metode penelitian', 'maksimal' => 10, 'urutan' => 2],
        ],
    ]);

    $templateDospem = AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'tipe_penilai' => 'dospem',
        'nama' => 'Template Pembimbing TI',
        'nilai_penyebut' => 15,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Sistematika penyusunan materi TA', 'maksimal' => 10, 'urutan' => 1],
            ['name' => 'Aktivitas konsultasi', 'maksimal' => 5, 'urutan' => 2],
        ],
    ]);

    $mahasiswa = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);
    $dosenPenguji = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $dospem = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);

    $schedule = Schedule::factory()->create(['jenis_sidang_id' => $jenis->id]);
    $schedule->dosens()->attach($dosenPenguji->id);

    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Sistem Informasi Manajemen Aset',
    ]);

    $formPenguji = AssessmentForm::create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosenPenguji->id,
        'tipe_penilai' => 'penguji',
        'template_id' => $templatePenguji->id,
        'skor_per_item' => [
            ['item' => 0, 'skor' => 8],
            ['item' => 1, 'skor' => 9],
        ],
        'skor_total' => 70.8,
        'catatan' => 'Perbaiki metode penelitian.',
    ]);

    $formDospem = AssessmentForm::create([
        'submission_id' => $submission->id,
        'dosen_id' => $dospem->id,
        'tipe_penilai' => 'dospem',
        'template_id' => $templateDospem->id,
        'skor_per_item' => [
            ['item' => 0, 'skor' => 8],
            ['item' => 1, 'skor' => 4],
        ],
        'skor_total' => 80,
        'catatan' => 'Lanjutkan ke sidang.',
    ]);

    return compact('prodi', 'jenis', 'templatePenguji', 'templateDospem', 'mahasiswa', 'dosenPenguji', 'dospem', 'schedule', 'submission', 'formPenguji', 'formDospem');
}

test('admin dapat mengakses halaman cetak penilaian', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.rekap.cetak-penilaian'));

    $response->assertOk();
});

test('non-admin tidak bisa akses halaman cetak penilaian', function () {
    $mhs = User::factory()->mahasiswa()->create();

    $this->actingAs($mhs)->get(route('admin.rekap.cetak-penilaian'))->assertStatus(403);
});

test('halaman cetak penilaian menampilkan submission yang sudah dinilai', function () {
    $s = scenarioAdminCetak();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.rekap.cetak-penilaian'));

    $response->assertOk();
    $response->assertSee($s['mahasiswa']->name);
    $response->assertSee('Cetak Penguji');
    $response->assertSee('Cetak Pembimbing');
});

test('halaman cetak penilaian tidak menampilkan submission yang belum dinilai', function () {
    $s = scenarioAdminCetak();
    $admin = User::factory()->admin()->create();
    $submissionLain = Submission::factory()->create(['user_id' => $s['mahasiswa']->id]);

    $response = $this->actingAs($admin)->get(route('admin.rekap.cetak-penilaian'));

    $response->assertOk();
    $response->assertDontSee($submissionLain->judul_laporan);
});

test('template dospem dan penguji memiliki item berbeda', function () {
    $s = scenarioAdminCetak();

    expect($s['templatePenguji']->items[0]['name'])->not->toBe($s['templateDospem']->items[0]['name']);
    expect(count($s['templatePenguji']->items))->toBe(2);
    expect(count($s['templateDospem']->items))->toBe(2);
});

test('halaman mahasiswa tidak menampilkan skor atau tombol cetak', function () {
    $s = scenarioAdminCetak();

    $response = $this->actingAs($s['mahasiswa'])->get(route('mahasiswa.penilaian.show', $s['submission']));

    $response->assertOk();
    $response->assertSee($s['dosenPenguji']->name);
    $response->assertSee($s['dospem']->name);
    $response->assertDontSee('Skor');
    $response->assertDontSee('Cetak');
    $response->assertDontSee('70.8');
    $response->assertDontSee('Sasaran Penilaian');
});
