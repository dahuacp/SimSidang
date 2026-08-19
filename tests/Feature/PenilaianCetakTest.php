<?php

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\Fakultas;
use App\Models\JenisSidang;
use App\Models\Prodi;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

function scenarioCetak(string $tipePenilai = 'penguji'): array
{
    $fakultas = Fakultas::factory()->create([
        'kode_fakultas' => 'FTIK',
        'nama_fakultas' => 'Fakultas Teknologi Informasi dan Komunikasi',
    ]);

    $prodi = Prodi::factory()->create([
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Informatika',
        'fakultas_id' => $fakultas->id,
    ]);

    Prodi::factory()->create([
        'kode_prodi' => 'SI',
        'nama_prodi' => 'Sistem Informasi',
        'fakultas_id' => $fakultas->id,
    ]);

    $jenis = JenisSidang::factory()->create(['nama' => 'Tugas Akhir']);

    $template = AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'tipe_penilai' => $tipePenilai,
        'nama' => 'Template TA TI',
        'nilai_penyebut' => 15,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Sistematika penyusunan materi TA', 'maksimal' => 10, 'urutan' => 1],
            ['name' => 'Ketepatan penggunaan istilah dan bahasa', 'maksimal' => 5, 'urutan' => 2],
            ['name' => 'Up to date materi yang dibahas', 'maksimal' => 10, 'urutan' => 3],
        ],
    ]);

    $mahasiswa = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);

    $dospem1 = User::factory()->dosen()->create(['prodi_id' => $prodi->id, 'name' => 'Winarti, S.Kom., M.Kom.']);
    $dospem2 = User::factory()->dosen()->create(['prodi_id' => $prodi->id, 'name' => 'Arif Rahman Sudjatmika, S.Kom., M.Kom.']);
    $dosen = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);

    $mahasiswa->dosenPembimbingByUrutan()->attach([$dospem1->id => ['urutan' => 1], $dospem2->id => ['urutan' => 2]]);

    $schedule = Schedule::factory()->create(['jenis_sidang_id' => $jenis->id]);
    $schedule->dosens()->attach([$dosen->id]);

    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Analisis Performa Website dengan Metode Stress Testing',
    ]);

    $form = AssessmentForm::create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'tipe_penilai' => $tipePenilai,
        'template_id' => $template->id,
        'skor_per_item' => [
            ['item' => 0, 'skor' => 8],
            ['item' => 1, 'skor' => 4],
            ['item' => 2, 'skor' => 10],
        ],
        'skor_total' => 146.7,
        'catatan' => 'Lengkapi analisis pengujian sistem.',
    ]);

    return compact('fakultas', 'prodi', 'jenis', 'template', 'mahasiswa', 'dospem1', 'dospem2', 'dosen', 'schedule', 'submission', 'form');
}

test('admin dapat mencetak penilaian submission', function () {
    $s = scenarioCetak();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.penilaian.cetak', [$s['submission'], $s['form']]));

    $response->assertOk();
    $response->assertHeaderContains('Content-Type', 'application/pdf');
});

test('dosen dapat mencetak penilaian yang dibuatnya', function () {
    $s = scenarioCetak();
    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.cetak', $s['form']));

    $response->assertOk();
    $response->assertHeaderContains('Content-Type', 'application/pdf');
});

test('dosen yang tidak terlibat tidak bisa mencetak penilaian', function () {
    $s = scenarioCetak();
    $other = User::factory()->dosen()->create();

    $this->actingAs($other)->get(route('dosen.penilaian.cetak', $s['form']))->assertStatus(403);
});

test('mahasiswa tidak dapat mencetak penilaian (403)', function () {
    $s = scenarioCetak();
    $response = $this->actingAs($s['mahasiswa'])->get(route('mahasiswa.penilaian.cetak', [$s['submission'], $s['form']]));

    $response->assertStatus(403);
});

test('mahasiswa tidak bisa mencetak penilaian submission orang lain (403)', function () {
    $s = scenarioCetak();
    $other = User::factory()->mahasiswa()->create();

    $this->actingAs($other)->get(route('mahasiswa.penilaian.cetak', [$s['submission'], $s['form']]))->assertStatus(403);
});

test('halaman cetak menampilkan data fakultas dan prodi mahasiswa', function () {
    $s = scenarioCetak();
    $admin = User::factory()->admin()->create();

    $view = view('penilaian.cetak', [
        'assessmentForm' => $s['form']->load([
            'submission.user.prodi.fakultas',
            'submission.schedule.jenisSidang',
            'dosen',
            'template',
        ]),
        'dospem' => $s['mahasiswa']->dosenPembimbingByUrutan->load('prodi'),
        'university' => config('university'),
    ])->render();

    expect($view)
        ->toContain('Fakultas Teknologi Informasi dan Komunikasi')
        ->toContain('Teknik Informatika')
        ->toContain('Sistem Informasi')
        ->toContain('Winarti, S.Kom., M.Kom.')
        ->toContain('Arif Rahman Sudjatmika, S.Kom., M.Kom.')
        ->toContain('EVALUASI PENILAIAN SIDANG');
});

test('kolom nilai mengikuti kelipatan maksimal item', function () {
    $s = scenarioCetak();
    $admin = User::factory()->admin()->create();

    $view = view('penilaian.cetak', [
        'assessmentForm' => $s['form']->load([
            'submission.user.prodi.fakultas',
            'submission.schedule.jenisSidang',
            'dosen',
            'template',
        ]),
        'dospem' => $s['mahasiswa']->dosenPembimbingByUrutan->load('prodi'),
        'university' => config('university'),
    ])->render();

    // maksimal 10 -> 2,4,6,8,10; maksimal 5 -> 1,2,3,4,5
    expect($view)
        ->toContain('2')
        ->toContain('10')
        ->toContain('5');
});
