<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin dapat login menggunakan username', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('kaspe')]);

    $response = $this->post('/login', [
        'username' => 'telo',
        'password' => 'kaspe',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($admin);
});

test('mahasiswa dapat login menggunakan NIM', function () {
    $mahasiswa = User::factory()->mahasiswa()->create([
        'username' => '20200101099',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'username' => '20200101099',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($mahasiswa);
});

test('login ditolak dengan username salah', function () {
    User::factory()->admin()->create();

    $response = $this->post('/login', [
        'username' => 'username_tidak_ada',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('login ditolak dengan password salah', function () {
    User::factory()->admin()->create(['username' => 'telo']);

    $response = $this->post('/login', [
        'username' => 'telo',
        'password' => 'salah',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('dashboard mengarahkan sesuai role', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $dosen = User::factory()->dosen()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($mahasiswa)->get('/dashboard')->assertRedirect(route('mahasiswa.submissions.index'));
    $this->actingAs($dosen)->get('/dashboard')->assertRedirect(route('dosen.submissions.index'));
    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
});

test('halaman login tersedia', function () {
    $this->get('/login')->assertStatus(200)->assertSee('NIM / NIDN');
});
