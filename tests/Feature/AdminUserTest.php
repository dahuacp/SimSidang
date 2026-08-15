<?php

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin dapat membuat user baru dengan password terhash', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('kaspe')]);
    $prodi = Prodi::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'User Baru',
        'username' => 'ubaru',
        'email' => 'ubaru@example.test',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'mahasiswa',
        'prodi_id' => $prodi->id,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'username' => 'ubaru',
        'role' => 'mahasiswa',
        'prodi_id' => $prodi->id,
    ]);
    $this->assertTrue(Hash::check('secret123', User::where('username', 'ubaru')->first()->password));
});

test('admin dapat mengedit user dan reset password', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('kaspe')]);
    $prodi = Prodi::factory()->create();
    $target = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
        'name' => 'Nama Diubah',
        'username' => $target->username,
        'email' => $target->email,
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
        'role' => 'mahasiswa',
        'prodi_id' => $prodi->id,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Nama Diubah', 'prodi_id' => $prodi->id]);
    $this->assertTrue(Hash::check('newpass123', $target->fresh()->password));
});

test('admin dapat menghapus user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->dosen()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $target));

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('admin dapat mencari user', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Cari Saya Ini', 'username' => 'cari001']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Cari Saya Ini']));

    $response->assertOk();
    $response->assertSee('Cari Saya Ini');
});

test('non-admin tidak bisa akses user CRUD', function () {
    $mhs = User::factory()->mahasiswa()->create();

    $this->actingAs($mhs)->get(route('admin.users.index'))->assertStatus(403);
});

test('validasi menolak pembuatan mahasiswa tanpa prodi', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->fromRoute('admin.users.create')->post(route('admin.users.store'), [
        'name' => 'User Baru',
        'username' => 'ubaru',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'mahasiswa',
    ]);

    $response->assertRedirect(route('admin.users.create'));
    $response->assertSessionHasErrors('prodi_id');
    $this->assertDatabaseMissing('users', ['username' => 'ubaru']);
});

test('admin dapat membuat user admin tanpa prodi', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Admin Baru',
        'username' => 'adminbaru',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'username' => 'adminbaru',
        'role' => 'admin',
        'prodi_id' => null,
    ]);
});
