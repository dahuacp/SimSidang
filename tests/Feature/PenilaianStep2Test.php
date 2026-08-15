<?php

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
use App\Models\Submission;
use App\Models\User;

function makeTemplate(): AssessmentTemplate
{
    $prodi = Prodi::factory()->create();
    $jenis = JenisSidang::factory()->create();

    return AssessmentTemplate::create([
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'nama' => 'Template Ujian',
        'nilai_penyebut' => 15,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Kualitas Laporan', 'maksimal' => 5, 'urutan' => 1],
            ['name' => 'Penguasaan Materi', 'maksimal' => 5, 'urutan' => 2],
            ['name' => 'Presentasi', 'maksimal' => 5, 'urutan' => 3],
        ],
    ]);
}

test('skor total mengikuti rumus sum/a x b', function () {
    $template = makeTemplate();

    $total = $template->calculateTotal([
        ['item' => 0, 'skor' => 4],
        ['item' => 1, 'skor' => 5],
        ['item' => 2, 'skor' => 3],
    ]);

    expect($total)->toBe(80.0); // (4+5+3)/15*100
});

test('skor total dibulatkan satu desimal', function () {
    $template = makeTemplate();

    $total = $template->calculateTotal([
        ['item' => 0, 'skor' => 4],
        ['item' => 1, 'skor' => 4],
        ['item' => 2, 'skor' => 4],
    ]);

    expect($total)->toBe(80.0);
});

test('skor total mengabaikan penyebut bernilai 0 (guard div by zero)', function () {
    $template = makeTemplate();
    $template->update(['nilai_penyebut' => 0]);

    $total = $template->calculateTotal([
        ['item' => 0, 'skor' => 5],
        ['item' => 1, 'skor' => 5],
        ['item' => 2, 'skor' => 5],
    ]);

    expect($total)->toBe(1500.0); // 15/1*100 (guard: penyebut di-set min 1, tidak error div by zero)
});

test('assessment form menyimpan skor_total otomatis saat create', function () {
    $template = makeTemplate();
    $submission = Submission::factory()->create();
    $dosen = User::factory()->dosen()->create();

    $form = AssessmentForm::create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'tipe_penilai' => 'penguji',
        'template_id' => $template->id,
        'skor_per_item' => [
            ['item' => 0, 'skor' => 4],
            ['item' => 1, 'skor' => 5],
            ['item' => 2, 'skor' => 3],
        ],
    ]);

    expect($form->skor_total)->toBe(80.0);
});

test('admin dapat melihat daftar template penilaian', function () {
    $admin = User::factory()->admin()->create();
    AssessmentTemplate::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get(route('admin.assessment-templates.index'));

    $response->assertOk();
    $response->assertViewHas('templates');
});

test('admin dapat membuat template penilaian', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.assessment-templates.store'), [
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'nama' => 'Template Sidang TA',
        'nilai_penyebut' => 15,
        'nilai_pengali' => 100,
        'items' => [
            ['name' => 'Kualitas Laporan', 'maksimal' => 5, 'urutan' => 1, 'bobot' => 1],
        ],
    ]);

    $response->assertRedirect(route('admin.assessment-templates.index'));

    $this->assertDatabaseHas('assessment_templates', [
        'prodi_id' => $prodi->id,
        'jenis_sidang_id' => $jenis->id,
        'nama' => 'Template Sidang TA',
    ]);
});

test('template penilaian unik per kombinasi prodi dan jenis sidang', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();
    $jenis = JenisSidang::factory()->create();

    AssessmentTemplate::factory()->create(['prodi_id' => $prodi->id, 'jenis_sidang_id' => $jenis->id]);

    $response = $this->actingAs($admin)->fromRoute('admin.assessment-templates.create')
        ->post(route('admin.assessment-templates.store'), [
            'prodi_id' => $prodi->id,
            'jenis_sidang_id' => $jenis->id,
            'nama' => 'Duplikat',
            'nilai_penyebut' => 15,
            'nilai_pengali' => 100,
            'items' => [
                ['name' => 'Item', 'maksimal' => 5, 'urutan' => 1],
            ],
        ]);

    $response->assertSessionHasErrors('prodi_id');
});

test('validasi menolak nilai_penyebut kurang dari 1', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.assessment-templates.create')
        ->post(route('admin.assessment-templates.store'), [
            'prodi_id' => $prodi->id,
            'jenis_sidang_id' => $jenis->id,
            'nama' => 'Invalid',
            'nilai_penyebut' => 0,
            'nilai_pengali' => 100,
            'items' => [
                ['name' => 'Item', 'maksimal' => 5, 'urutan' => 1],
            ],
        ]);

    $response->assertSessionHasErrors('nilai_penyebut');
});

test('validasi menolak items kosong', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.assessment-templates.create')
        ->post(route('admin.assessment-templates.store'), [
            'prodi_id' => $prodi->id,
            'jenis_sidang_id' => $jenis->id,
            'nama' => 'Invalid',
            'nilai_penyebut' => 15,
            'nilai_pengali' => 100,
            'items' => [],
        ]);

    $response->assertSessionHasErrors('items');
});

test('non-admin tidak dapat mengakses template penilaian', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('admin.assessment-templates.index'));

    $response->assertForbidden();
});

test('dosen tidak dapat mengakses halaman admin template penilaian', function () {
    $dosen = User::factory()->dosen()->create();

    $response = $this->actingAs($dosen)->get(route('admin.assessment-templates.create'));

    $response->assertForbidden();
});

test('admin dapat mengupdate template penilaian', function () {
    $admin = User::factory()->admin()->create();
    $template = AssessmentTemplate::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.assessment-templates.update', $template), [
        'prodi_id' => $template->prodi_id,
        'jenis_sidang_id' => $template->jenis_sidang_id,
        'nama' => 'Template Update',
        'nilai_penyebut' => 10,
        'nilai_pengali' => 50,
        'items' => [
            ['name' => 'Item Baru', 'maksimal' => 10, 'urutan' => 1],
        ],
    ]);

    $response->assertRedirect(route('admin.assessment-templates.index'));

    $this->assertDatabaseHas('assessment_templates', [
        'id' => $template->id,
        'nama' => 'Template Update',
        'nilai_penyebut' => 10,
        'nilai_pengali' => 50,
    ]);
});

test('admin dapat menghapus template penilaian tanpa form terisi', function () {
    $admin = User::factory()->admin()->create();
    $template = AssessmentTemplate::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.assessment-templates.index')
        ->delete(route('admin.assessment-templates.destroy', $template));

    $response->assertRedirect(route('admin.assessment-templates.index'));
    $this->assertDatabaseMissing('assessment_templates', ['id' => $template->id]);
});

test('admin tidak dapat menghapus template yang sudah dipakai form', function () {
    $admin = User::factory()->admin()->create();
    $form = AssessmentForm::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.assessment-templates.index')
        ->delete(route('admin.assessment-templates.destroy', $form->template));

    $response->assertRedirect(route('admin.assessment-templates.index'));
    $this->assertDatabaseHas('assessment_templates', ['id' => $form->template_id]);
});
