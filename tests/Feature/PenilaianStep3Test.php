<?php

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

function scenarioPenilaian(string $role = 'penguji'): array
{
    $prodi = Prodi::factory()->create();
    $jenis = JenisSidang::factory()->create();

    $template = AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'nama' => 'Template Sidang TA',
        'nilai_penyebut' => 15,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Kualitas Laporan', 'maksimal' => 5, 'urutan' => 1],
            ['name' => 'Penguasaan Materi', 'maksimal' => 5, 'urutan' => 2],
            ['name' => 'Presentasi', 'maksimal' => 5, 'urutan' => 3],
        ],
    ]);

    $mahasiswa = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);
    $dosen = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $schedule = Schedule::factory()->create(['jenis_sidang_id' => $jenis->id]);
    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'judul_laporan' => 'Sistem Penilaian Sidang',
    ]);

    if ($role === 'penguji') {
        $schedule->dosens()->attach($dosen->id);
    } elseif ($role === 'dospem') {
        $mahasiswa->dosenPembimbing()->attach($dosen->id);
    }

    return compact('prodi', 'jenis', 'template', 'mahasiswa', 'dosen', 'schedule', 'submission');
}

function payloadPenilaian(array $overrides = []): array
{
    return array_merge([
        'tipe_penilai' => 'penguji',
        'catatan' => 'Pertahankan konsistensi format.',
        'skor_per_item' => [
            ['item' => 0, 'skor' => 4],
            ['item' => 1, 'skor' => 5],
            ['item' => 2, 'skor' => 3],
        ],
    ], $overrides);
}

test('dosen penguji dapat mengakses halaman index penilaian', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.index'));

    $response->assertOk();
    $response->assertSee($s['submission']->judul_laporan);
});

test('dosen penguji melihat submission di tab penguji', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.index'));

    $response->assertOk();
    $response->assertSee('Penguji');
    $response->assertSee($s['submission']->judul_laporan);
});

test('dosen pembimbing melihat submission bimbingan di tab pembimbing', function () {
    $s = scenarioPenilaian('dospem');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.index'));

    $response->assertOk();
    $response->assertSee('Pembimbing');
    $response->assertSee($s['submission']->judul_laporan);
});

test('dosen penguji dapat membuka form isi penilaian', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.create', [
        'submission' => $s['submission'],
        'tipe' => 'penguji',
    ]));

    $response->assertOk();
    $response->assertSee('Kualitas Laporan');
});

test('dosen dospem dapat membuka form isi penilaian untuk bimbingannya', function () {
    $s = scenarioPenilaian('dospem');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.create', [
        'submission' => $s['submission'],
        'tipe' => 'dospem',
    ]));

    $response->assertOk();
    $response->assertSee('Penguasaan Materi');
});

test('dosen yang bukan penguji tidak dapat mengisi penilaian sebagai penguji', function () {
    $s = scenarioPenilaian('dospem');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.create', [
        'submission' => $s['submission'],
        'tipe' => 'penguji',
    ]));

    $response->assertForbidden();
});

test('dosen yang bukan pembimbing tidak dapat mengisi penilaian sebagai dospem', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.create', [
        'submission' => $s['submission'],
        'tipe' => 'dospem',
    ]));

    $response->assertForbidden();
});

test('dosen penguji dapat menyimpan penilaian', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian());

    $response->assertRedirect();
    $this->assertDatabaseHas('assessment_forms', [
        'submission_id' => $s['submission']->id,
        'dosen_id' => $s['dosen']->id,
        'tipe_penilai' => 'penguji',
    ]);
});

test('penilaian yang disimpan menghitung skor total', function () {
    $s = scenarioPenilaian('penguji');

    $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian());

    $form = AssessmentForm::where('submission_id', $s['submission']->id)->first();
    expect($form->skor_total)->toBe(80.0);
});

test('dosen dospem dapat menyimpan penilaian untuk bimbingannya', function () {
    $s = scenarioPenilaian('dospem');

    $response = $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian([
        'tipe_penilai' => 'dospem',
    ]));

    $response->assertRedirect();
    $this->assertDatabaseHas('assessment_forms', [
        'submission_id' => $s['submission']->id,
        'dosen_id' => $s['dosen']->id,
        'tipe_penilai' => 'dospem',
    ]);
});

test('satu form per kombinasi submission, dosen, dan tipe', function () {
    $s = scenarioPenilaian('penguji');

    $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian());
    $response = $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian());

    $response->assertRedirect();
    expect(AssessmentForm::where('submission_id', $s['submission']->id)
        ->where('dosen_id', $s['dosen']->id)
        ->where('tipe_penilai', 'penguji')->count())->toBe(1);
});

test('dosen ganda (penguji dan pembimbing) dapat mengisi dua form terpisah', function () {
    $s = scenarioPenilaian('penguji');
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen']->id);

    $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian());
    $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian([
        'tipe_penilai' => 'dospem',
    ]));

    expect(AssessmentForm::where('submission_id', $s['submission']->id)
        ->where('dosen_id', $s['dosen']->id)->count())->toBe(2);
});

test('validasi menolak skor melebihi maksimal item', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian([
        'skor_per_item' => [
            ['item' => 0, 'skor' => 9],
        ],
    ]));

    $response->assertSessionHasErrors('skor_per_item.0.skor');
});

test('validasi menolak tipe_penilai tidak valid', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['dosen'])->post(route('dosen.penilaian.store', $s['submission']), payloadPenilaian([
        'tipe_penilai' => 'kaprodi',
    ]));

    $response->assertSessionHasErrors('tipe_penilai');
});

test('mahasiswa tidak dapat mengisi form penilaian', function () {
    $s = scenarioPenilaian('penguji');

    $response = $this->actingAs($s['mahasiswa'])->get(route('dosen.penilaian.create', [
        'submission' => $s['submission'],
        'tipe' => 'penguji',
    ]));

    $response->assertForbidden();
});

test('mahasiswa dapat melihat ringkasan penilaian read-only', function () {
    $s = scenarioPenilaian('penguji');
    AssessmentForm::factory()->create([
        'submission_id' => $s['submission']->id,
        'dosen_id' => $s['dosen']->id,
        'tipe_penilai' => 'penguji',
        'template_id' => $s['template']->id,
    ]);

    $response = $this->actingAs($s['mahasiswa'])->get(route('mahasiswa.penilaian.show', $s['submission']));

    $response->assertOk();
    $response->assertSee('Penilaian');
    $response->assertSee('skor_total' !== '' ? number_format(AssessmentForm::where('submission_id', $s['submission']->id)->first()->skor_total, 1) : '');
});

test('mahasiswa tidak dapat melihat penilaian submission milik orang lain', function () {
    $s = scenarioPenilaian('penguji');
    $lain = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($lain)->get(route('mahasiswa.penilaian.show', $s['submission']));

    $response->assertForbidden();
});

test('dosen penguji dapat mengedit penilaian miliknya', function () {
    $s = scenarioPenilaian('penguji');
    $form = AssessmentForm::factory()->create([
        'submission_id' => $s['submission']->id,
        'dosen_id' => $s['dosen']->id,
        'tipe_penilai' => 'penguji',
        'template_id' => $s['template']->id,
    ]);

    $response = $this->actingAs($s['dosen'])->get(route('dosen.penilaian.edit', $form));

    $response->assertOk();
    $response->assertSee('Perbarui Penilaian');
});

test('dosen lain tidak dapat mengedit penilaian milik dosen lain', function () {
    $s = scenarioPenilaian('penguji');
    $lain = User::factory()->dosen()->create(['prodi_id' => $s['prodi']->id]);
    $form = AssessmentForm::factory()->create([
        'submission_id' => $s['submission']->id,
        'dosen_id' => $s['dosen']->id,
        'tipe_penilai' => 'penguji',
        'template_id' => $s['template']->id,
    ]);

    $response = $this->actingAs($lain)->get(route('dosen.penilaian.edit', $form));

    $response->assertForbidden();
});
