<?php

use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\User;

test('admin dapat melihat daftar jenis sidang', function () {
    $admin = User::factory()->admin()->create();
    JenisSidang::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get(route('admin.jenis-sidangs.index'));

    $response->assertOk();
    $response->assertViewHas('jenisSidangs');
});

test('halaman edit jenis sidang menampilkan data yang ada', function () {
    $admin = User::factory()->admin()->create();
    $jenis = JenisSidang::factory()->create(['nama' => 'TA', 'deskripsi' => 'Tugas Akhir']);

    $response = $this->actingAs($admin)->get(route('admin.jenis-sidangs.edit', $jenis));

    $response->assertOk();
    $response->assertSee('TA');
    $response->assertSee('Tugas Akhir');
    $response->assertSee(route('admin.jenis-sidangs.update', $jenis));
});

test('admin dapat membuat jenis sidang baru', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.jenis-sidangs.store'), [
        'nama' => 'Seminar Hasil',
        'deskripsi' => 'Seminar hasil penelitian',
    ]);

    $response->assertRedirect(route('admin.jenis-sidangs.index'));
    $this->assertDatabaseHas('jenis_sidangs', ['nama' => 'Seminar Hasil']);
});

test('validasi menolak nama jenis sidang duplikat', function () {
    $admin = User::factory()->admin()->create();
    JenisSidang::factory()->create(['nama' => 'TA']);

    $response = $this->actingAs($admin)->fromRoute('admin.jenis-sidangs.create')
        ->post(route('admin.jenis-sidangs.store'), [
            'nama' => 'TA',
        ]);

    $response->assertSessionHasErrors('nama');
});

test('non-admin tidak dapat mengakses jenis sidang', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('admin.jenis-sidangs.index'));

    $response->assertForbidden();
});

test('admin dapat mengupdate jenis sidang', function () {
    $admin = User::factory()->admin()->create();
    $jenis = JenisSidang::factory()->create(['nama' => 'TA']);

    $response = $this->actingAs($admin)->put(route('admin.jenis-sidangs.update', $jenis), [
        'nama' => 'Tugas Akhir',
        'deskripsi' => 'Ujian TA',
    ]);

    $response->assertRedirect(route('admin.jenis-sidangs.index'));
    $this->assertDatabaseHas('jenis_sidangs', ['id' => $jenis->id, 'nama' => 'Tugas Akhir']);
});

test('admin dapat menghapus jenis sidang yang tidak dipakai jadwal', function () {
    $admin = User::factory()->admin()->create();
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.jenis-sidangs.index')
        ->delete(route('admin.jenis-sidangs.destroy', $jenis));

    $response->assertRedirect(route('admin.jenis-sidangs.index'));
    $this->assertDatabaseMissing('jenis_sidangs', ['id' => $jenis->id]);
});

test('admin tidak dapat menghapus jenis sidang yang masih dipakai jadwal', function () {
    $admin = User::factory()->admin()->create();
    $jenis = JenisSidang::factory()->create();
    Schedule::create([
        'nama_grup_sidang' => 'Sidang TA',
        'ruangan' => 'Ruang 1',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'jenis_sidang_id' => $jenis->id,
    ]);

    $response = $this->actingAs($admin)->fromRoute('admin.jenis-sidangs.index')
        ->delete(route('admin.jenis-sidangs.destroy', $jenis));

    $response->assertRedirect(route('admin.jenis-sidangs.index'));
    $this->assertDatabaseHas('jenis_sidangs', ['id' => $jenis->id]);
});
