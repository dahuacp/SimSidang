<?php

use App\Models\Fakultas;
use App\Models\Prodi;
use App\Models\User;

test('admin dapat melihat daftar fakultas', function () {
    $admin = User::factory()->admin()->create();
    Fakultas::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('admin.fakultas.index'));

    $response->assertOk();
    $response->assertViewHas('fakultas');
});

test('admin dapat membuat fakultas baru', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.fakultas.store'), [
        'kode_fakultas' => 'FTIK',
        'nama_fakultas' => 'Fakultas Teknologi Informasi dan Komunikasi',
    ]);

    $response->assertRedirect(route('admin.fakultas.index'));

    $this->assertDatabaseHas('fakultas', [
        'kode_fakultas' => 'FTIK',
        'nama_fakultas' => 'Fakultas Teknologi Informasi dan Komunikasi',
    ]);
});

test('validasi menolak kode_fakultas duplikat saat membuat', function () {
    $admin = User::factory()->admin()->create();
    Fakultas::factory()->create(['kode_fakultas' => 'FTIK']);

    $response = $this->actingAs($admin)->post(route('admin.fakultas.store'), [
        'kode_fakultas' => 'FTIK',
        'nama_fakultas' => 'Fakultas Teknik',
    ]);

    $response->assertSessionHasErrors('kode_fakultas');
    $this->assertDatabaseMissing('fakultas', ['nama_fakultas' => 'Fakultas Teknik']);
});

test('admin dapat mengedit fakultas', function () {
    $admin = User::factory()->admin()->create();
    $fakultas = Fakultas::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.fakultas.update', $fakultas), [
        'kode_fakultas' => 'FEB',
        'nama_fakultas' => 'Fakultas Ekonomi dan Bisnis',
    ]);

    $response->assertRedirect(route('admin.fakultas.index'));

    $this->assertDatabaseHas('fakultas', [
        'id' => $fakultas->id,
        'kode_fakultas' => 'FEB',
        'nama_fakultas' => 'Fakultas Ekonomi dan Bisnis',
    ]);
});

test('admin dapat menghapus fakultas yang tidak digunakan', function () {
    $admin = User::factory()->admin()->create();
    $fakultas = Fakultas::factory()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.fakultas.index')->delete(route('admin.fakultas.destroy', $fakultas));

    $response->assertRedirect(route('admin.fakultas.index'));
    $this->assertDatabaseMissing('fakultas', ['id' => $fakultas->id]);
});

test('admin tidak bisa menghapus fakultas yang memiliki prodi', function () {
    $admin = User::factory()->admin()->create();
    $fakultas = Fakultas::factory()->create();
    Prodi::factory()->create(['fakultas_id' => $fakultas->id]);

    $response = $this->actingAs($admin)->delete(route('admin.fakultas.destroy', $fakultas));

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('fakultas', ['id' => $fakultas->id]);
});

test('non-admin tidak bisa akses fakultas CRUD', function () {
    $mhs = User::factory()->mahasiswa()->create();

    $this->actingAs($mhs)->get(route('admin.fakultas.index'))->assertStatus(403);
});

test('admin dapat mencari fakultas', function () {
    $admin = User::factory()->admin()->create();
    Fakultas::factory()->create(['kode_fakultas' => 'FTIK', 'nama_fakultas' => 'Fakultas Teknik']);
    Fakultas::factory()->create(['kode_fakultas' => 'FEB', 'nama_fakultas' => 'Fakultas Ekonomi']);

    $response = $this->actingAs($admin)->get(route('admin.fakultas.index', ['search' => 'Ekonomi']));

    $response->assertOk();
    $response->assertSee('Fakultas Ekonomi');
    $response->assertSee('FEB');
});
