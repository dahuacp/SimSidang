<?php

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\RekapNilaiService;

function scenarioCetakNilai(): array
{
    $prodi = Prodi::factory()->create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);
    $jenis = JenisSidang::factory()->create(['nama' => 'TA']);

    $templatePenguji = AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'tipe_penilai' => 'penguji',
        'nama' => 'Template Penguji TI',
        'nilai_penyebut' => 12,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Penguasaan materi', 'maksimal' => 10, 'urutan' => 1],
            ['name' => 'Ketepatan metode', 'maksimal' => 10, 'urutan' => 2],
        ],
    ]);

    $templateDospem = AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'tipe_penilai' => 'dospem',
        'nama' => 'Template Dospem TI',
        'nilai_penyebut' => 15,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Sistematika', 'maksimal' => 10, 'urutan' => 1],
            ['name' => 'Konsultasi', 'maksimal' => 5, 'urutan' => 2],
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
        'judul_laporan' => 'Sistem Informasi Manajemen',
    ]);

    AssessmentForm::create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosenPenguji->id,
        'tipe_penilai' => 'penguji',
        'template_id' => $templatePenguji->id,
        'skor_per_item' => [['item' => 0, 'skor' => 8], ['item' => 1, 'skor' => 9]],
        'skor_total' => 70.8,
        'catatan' => 'Perbaiki metode.',
    ]);

    AssessmentForm::create([
        'submission_id' => $submission->id,
        'dosen_id' => $dospem->id,
        'tipe_penilai' => 'dospem',
        'template_id' => $templateDospem->id,
        'skor_per_item' => [['item' => 0, 'skor' => 8], ['item' => 1, 'skor' => 4]],
        'skor_total' => 80,
        'catatan' => 'Lanjutkan.',
    ]);

    return compact('prodi', 'jenis', 'templatePenguji', 'templateDospem', 'mahasiswa', 'dosenPenguji', 'dospem', 'schedule', 'submission');
}

test('admin dapat mengakses halaman rekap nilai', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.rekap.nilai'))->assertOk();
});

test('halaman rekap nilai menampilkan data mahasiswa', function () {
    $s = scenarioCetakNilai();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.rekap.nilai'))
        ->assertOk()
        ->assertSee($s['mahasiswa']->name)
        ->assertSee('Rekap Hasil Penilaian')
        ->assertSee('Semua Prodi');
});

test('rekap nilai dapat difilter per prodi', function () {
    $s = scenarioCetakNilai();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.rekap.nilai', ['prodi_id' => $s['prodi']->id]))
        ->assertOk()
        ->assertSee($s['prodi']->nama);
});

test('rekap nilai dapat diurutkan', function () {
    $admin = User::factory()->admin()->create();
    Prodi::factory()->count(2)->create();
    JenisSidang::factory()->create();

    $mahasiswa1 = User::factory()->mahasiswa()->create(['prodi_id' => Prodi::first()->id]);
    $mahasiswa2 = User::factory()->mahasiswa()->create(['prodi_id' => Prodi::first()->id]);

    foreach ([1] as $nm) {
        Schedule::factory()->create(['jenis_sidang_id' => JenisSidang::first()->id])->dosens()->attach(
            User::factory()->dosen()->create(['prodi_id' => Prodi::first()->id])->id
        );
    }

    $this->actingAs($admin)->get(route('admin.rekap.nilai', ['sort' => 'desc']))->assertOk();
    $this->actingAs($admin)->get(route('admin.rekap.nilai', ['sort' => 'asc']))->assertOk();
});

test('rekap nilai eksekusi excel download', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.rekap.nilai-excel'))
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('service computes nominal scores from skor_total', function () {
    $s = scenarioCetakNilai();
    $service = app(RekapNilaiService::class);
    $rows = $service->getRows();

    expect($rows)->not->toBeEmpty();
    expect($rows[0]['mahasiswa'])->toBe($s['mahasiswa']->name);
    // Nominal values (not percentage) - from skor_total of assessment forms
    expect($rows[0]['dospem_nilai'])->toBeNumeric();
    expect($rows[0]['penguji_nilai'])->toBeNumeric();
    expect($rows[0]['nilai_akhir'])->toBeNumeric();
});

test('service chart data contains distribution', function () {
    $s = scenarioCetakNilai();
    $admin = User::factory()->admin()->create();

    $service = app(RekapNilaiService::class);
    $chartData = $service->getChartData();

    expect($chartData)->toHaveKey('distribution');
    expect($chartData['distribution'])->toHaveKey('A');
    expect($chartData['distribution'])->toHaveKey('B');
    expect($chartData)->toHaveKey('perProdi');
});
