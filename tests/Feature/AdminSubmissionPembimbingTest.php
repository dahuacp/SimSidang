<?php

use App\Models\Prodi;
use App\Models\Submission;
use App\Models\User;

function scenarioPembimbing(): array
{
    $prodi = Prodi::factory()->create();
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);
    $dosen1 = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $dosen2 = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $dosen3 = User::factory()->dosen()->create(['prodi_id' => $prodi->id]);
    $submission = Submission::factory()->create(['user_id' => $mahasiswa->id]);

    return compact('prodi', 'admin', 'mahasiswa', 'dosen1', 'dosen2', 'dosen3', 'submission');
}

test('admin dapat melihat section assign dosen pembimbing di detail submission', function () {
    $s = scenarioPembimbing();

    $response = $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['submission']));

    $response->assertOk();
    $response->assertSee('Dosen Pembimbing');
    $response->assertSee('maks. 2');
});

test('admin dapat assign dosen pembimbing I dan II', function () {
    $s = scenarioPembimbing();

    $response = $this->actingAs($s['admin'])->post(route('admin.submissions.pembimbing.store', $s['submission']), [
        'dosen_id' => [$s['dosen1']->id, $s['dosen2']->id],
    ]);

    $response->assertRedirect(route('admin.submissions.show', $s['submission']));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen1']->id,
        'urutan' => 1,
    ]);
    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen2']->id,
        'urutan' => 2,
    ]);
});

test('admin dapat assign hanya pembimbing I', function () {
    $s = scenarioPembimbing();

    $response = $this->actingAs($s['admin'])->post(route('admin.submissions.pembimbing.store', $s['submission']), [
        'dosen_id' => [$s['dosen1']->id],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen1']->id,
        'urutan' => 1,
    ]);
    $this->assertDatabaseMissing('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'urutan' => 2,
    ]);
});

test('validasi menolak dosen pembimbing I dan II sama', function () {
    $s = scenarioPembimbing();

    $response = $this->actingAs($s['admin'])->fromRoute('admin.submissions.show', $s['submission'])
        ->post(route('admin.submissions.pembimbing.store', $s['submission']), [
            'dosen_id' => [$s['dosen1']->id, $s['dosen1']->id],
        ]);

    $response->assertSessionHasErrors('dosen_id');
});

test('validasi menolak dosen dengan prodi berbeda dari mahasiswa', function () {
    $s = scenarioPembimbing();
    $prodiLain = Prodi::factory()->create();
    $dosenLain = User::factory()->dosen()->create(['prodi_id' => $prodiLain->id]);

    $response = $this->actingAs($s['admin'])->fromRoute('admin.submissions.show', $s['submission'])
        ->post(route('admin.submissions.pembimbing.store', $s['submission']), [
            'dosen_id' => [$dosenLain->id],
        ]);

    $response->assertSessionHasErrors('dosen_id.0');
});

test('validasi menolak user non-dosen sebagai pembimbing', function () {
    $s = scenarioPembimbing();
    $nonDosen = User::factory()->mahasiswa()->create(['prodi_id' => $s['prodi']->id]);

    $response = $this->actingAs($s['admin'])->fromRoute('admin.submissions.show', $s['submission'])
        ->post(route('admin.submissions.pembimbing.store', $s['submission']), [
            'dosen_id' => [$nonDosen->id],
        ]);

    $response->assertSessionHasErrors('dosen_id.0');
});

test('admin dapat menghapus dosen pembimbing per slot', function () {
    $s = scenarioPembimbing();
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen1']->id, ['urutan' => 1]);
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen2']->id, ['urutan' => 2]);

    $response = $this->actingAs($s['admin'])->delete(route('admin.submissions.pembimbing.destroy', [$s['submission'], $s['dosen1']]), [
        'urutan' => 1,
    ]);

    $response->assertRedirect(route('admin.submissions.show', $s['submission']));
    $this->assertDatabaseMissing('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen1']->id,
        'urutan' => 1,
    ]);
    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen2']->id,
        'urutan' => 2,
    ]);
});

test('non-admin tidak dapat assign dosen pembimbing', function () {
    $s = scenarioPembimbing();

    $response = $this->actingAs($s['mahasiswa'])->post(route('admin.submissions.pembimbing.store', $s['submission']), [
        'dosen_id' => [$s['dosen1']->id],
    ]);

    $response->assertForbidden();
});

test('assign pembimbing menggantikan pembimbing lama', function () {
    $s = scenarioPembimbing();
    $dosenLama = User::factory()->dosen()->create(['prodi_id' => $s['prodi']->id]);
    $s['mahasiswa']->dosenPembimbing()->attach($dosenLama->id, ['urutan' => 1]);

    $this->actingAs($s['admin'])->post(route('admin.submissions.pembimbing.store', $s['submission']), [
        'dosen_id' => [$s['dosen1']->id],
    ]);

    $this->assertDatabaseMissing('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $dosenLama->id,
    ]);
    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen1']->id,
        'urutan' => 1,
    ]);
});

test('halaman detail submission menampilkan searchableSelect untuk assign pembimbing', function () {
    $s = scenarioPembimbing();

    $html = $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['submission']))
        ->assertOk()
        ->assertSee('searchableSelect')
        ->assertSee('Dosen Pembimbing (maks. 2)')
        ->getContent();
});

test('halaman menampilkan data pembimbing lama pada initialSelected (tidak hilang saat submit)', function () {
    $s = scenarioPembimbing();
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen1']->id, ['urutan' => 1]);
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen2']->id, ['urutan' => 2]);

    $html = $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['submission']))
        ->assertOk()
        ->getContent();

    expect(strpos($html, 'initialSelected'))->not->toBeFalse();
    expect(strpos($html, 'multiple: true'))->not->toBeFalse();
    expect(strpos($html, 'name="dosen_id[]"'))->not->toBeFalse();
    expect(strpos($html, $s['dosen1']->name))->not->toBeFalse();
    expect(strpos($html, $s['dosen2']->name))->not->toBeFalse();
});

test('halaman menampilkan tampilan reaktif multi-select dengan urutan dan max 2', function () {
    $s = scenarioPembimbing();
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen1']->id, ['urutan' => 1]);
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen2']->id, ['urutan' => 2]);

    $html = $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['submission']))
        ->assertOk()
        ->getContent();

    expect(strpos($html, 'max: 2'))->not->toBeFalse();
    expect(strpos($html, 'x-show="maxedOut"'))->not->toBeFalse();
    expect(strpos($html, '@click="remove(item)"'))->not->toBeFalse();
    expect(strpos($html, "'Pembimbing ' + (index + 1)"))->not->toBeFalse();
    expect(strpos($html, 'name="dosen_id[]" :value="item.id"'))->not->toBeFalse();
});

test('urutan pembimbing mengikuti urutan pilihan dosen pada search bar', function () {
    $s = scenarioPembimbing();

    $this->actingAs($s['admin'])->post(route('admin.submissions.pembimbing.store', $s['submission']), [
        'dosen_id' => [$s['dosen2']->id, $s['dosen1']->id],
    ]);

    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen2']->id,
        'urutan' => 1,
    ]);
    $this->assertDatabaseHas('pembimbingan', [
        'mahasiswa_id' => $s['mahasiswa']->id,
        'dosen_id' => $s['dosen1']->id,
        'urutan' => 2,
    ]);
});

test('validasi menolak lebih dari 2 dosen pembimbing', function () {
    $s = scenarioPembimbing();

    $response = $this->actingAs($s['admin'])->fromRoute('admin.submissions.show', $s['submission'])
        ->post(route('admin.submissions.pembimbing.store', $s['submission']), [
            'dosen_id' => [$s['dosen1']->id, $s['dosen2']->id, $s['dosen3']->id],
        ]);

    $response->assertSessionHasErrors('dosen_id');
});

test('form store pembimbing tidak mengandung form bersarang (nested form)', function () {
    $s = scenarioPembimbing();
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen1']->id, ['urutan' => 1]);
    $s['mahasiswa']->dosenPembimbing()->attach($s['dosen2']->id, ['urutan' => 2]);

    $html = $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['submission']))
        ->assertOk()
        ->getContent();

    $storeFormStart = strpos($html, 'action="'.e(route('admin.submissions.pembimbing.store', $s['submission'])).'"');
    $storeFormEnd = strpos($html, '</form>', $storeFormStart + 5);

    expect($storeFormStart)->not->toBeFalse('Store form should exist');
    expect($storeFormEnd)->not->toBeFalse();

    $innerForms = substr_count(substr($html, $storeFormStart, $storeFormEnd - $storeFormStart), '<form ');
    expect($innerForms)->toBe(0);
});

test('card pembimbing menggunakan overflow-visible agar dropdown tidak ter-clip', function () {
    $s = scenarioPembimbing();

    $html = $this->actingAs($s['admin'])->get(route('admin.submissions.show', $s['submission']))
        ->assertOk()
        ->getContent();

    expect(strpos($html, 'class="overflow-visible rounded-2xl border'))->not->toBeFalse();
    expect(strpos($html, 'overflow-hidden'))->toBeFalse();
});
