<?php

use App\Models\Prodi;
use App\Models\User;

test('admin dapat melihat daftar program studi', function () {
    $admin = User::factory()->admin()->create();
    Prodi::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('admin.prodis.index'));

    $response->assertOk();
    $response->assertViewHas('prodis');
});

test('admin dapat membuat program studi baru', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.prodis.store'), [
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Informatika',
    ]);

    $response->assertRedirect(route('admin.prodis.index'));

    $this->assertDatabaseHas('prodis', [
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Informatika',
    ]);
});

test('validasi menolak kode_prodi duplikat saat membuat', function () {
    $admin = User::factory()->admin()->create();
    Prodi::factory()->create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);

    $response = $this->actingAs($admin)->post(route('admin.prodis.store'), [
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Sistem Informasi',
    ]);

    $response->assertSessionHasErrors('kode_prodi');
    $this->assertDatabaseMissing('prodis', ['nama_prodi' => 'Sistem Informasi']);
});

test('admin dapat mengedit program studi', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.prodis.update', $prodi), [
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Informatika',
    ]);

    $response->assertRedirect(route('admin.prodis.index'));

    $this->assertDatabaseHas('prodis', [
        'id' => $prodi->id,
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Informatika',
    ]);
});

test('admin dapat menghapus program studi yang tidak digunakan', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.prodis.index')->delete(route('admin.prodis.destroy', $prodi));

    $response->assertRedirect(route('admin.prodis.index'));
    $this->assertDatabaseMissing('prodis', ['id' => $prodi->id]);
});

test('admin tidak bisa menghapus program studi yang sedang dipakai', function () {
    $admin = User::factory()->admin()->create();
    $prodi = Prodi::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);

    $response = $this->actingAs($admin)->delete(route('admin.prodis.destroy', $prodi));

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('prodis', ['id' => $prodi->id]);
});

test('non-admin tidak bisa akses prodi CRUD', function () {
    $mhs = User::factory()->mahasiswa()->create();

    $this->actingAs($mhs)->get(route('admin.prodis.index'))->assertStatus(403);
});

test('admin dapat mencari program studi', function () {
    $admin = User::factory()->admin()->create();
    Prodi::factory()->create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);
    Prodi::factory()->create(['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi']);

    $response = $this->actingAs($admin)->get(route('admin.prodis.index', ['search' => 'Teknik Informatika']));

    $response->assertOk();
    $response->assertSee('Teknik Informatika');
    $response->assertSee('TI');
});
