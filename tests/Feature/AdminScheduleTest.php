<?php

use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\User;

test('admin dapat membuat jadwal dengan assign dosen', function () {
    $admin = User::factory()->admin()->create();
    $dosen1 = User::factory()->dosen()->create();
    $dosen2 = User::factory()->dosen()->create();
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Sidang A',
        'ruangan' => 'Ruang 1',
        'tanggal_sidang' => '2026-08-15',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen1->id, $dosen2->id],
    ]);

    $response->assertRedirect(route('admin.schedules.index'));

    $schedule = Schedule::first();
    $this->assertDatabaseHas('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $dosen1->id,
    ]);
    $this->assertDatabaseHas('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $dosen2->id,
    ]);
});

test('admin dapat mengedit jadwal dan mengganti dosen', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $jenis = JenisSidang::factory()->create();
    $d1 = User::factory()->dosen()->create();
    $d2 = User::factory()->dosen()->create();

    $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), [
        'nama_grup_sidang' => 'Updated',
        'ruangan' => 'Ruang 2',
        'tanggal_sidang' => $schedule->tanggal_sidang->format('Y-m-d'),
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$d2->id],
    ]);

    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'nama_grup_sidang' => 'Updated']);
    $this->assertDatabaseHas('schedule_dosen', ['schedule_id' => $schedule->id, 'user_id' => $d2->id]);
});

test('admin dapat menghapus jadwal', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();

    $this->actingAs($admin)->delete(route('admin.schedules.destroy', $schedule));

    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
});

test('admin dapat memplot mahasiswa ke jadwal', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $schedule), [
        'user_id' => $mahasiswa->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('schedule_mahasiswa', [
        'schedule_id' => $schedule->id,
        'user_id' => $mahasiswa->id,
    ]);
});

test('plot mahasiswa duplikat ditolak', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $schedule), [
        'user_id' => $mahasiswa->id,
    ])->assertRedirect();

    $this->from(route('admin.schedules.edit', $schedule))
        ->actingAs($admin)
        ->post(route('admin.schedules.mahasiswa.store', $schedule), [
            'user_id' => $mahasiswa->id,
        ])
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('schedule_mahasiswa', 1);
});

test('plot pengguna non-mahasiswa ditolak', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $dosen = User::factory()->dosen()->create();

    $this->from(route('admin.schedules.edit', $schedule))
        ->actingAs($admin)
        ->post(route('admin.schedules.mahasiswa.store', $schedule), [
            'user_id' => $dosen->id,
        ])
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('schedule_mahasiswa', 0);
});

test('admin dapat menghapus mahasiswa dari jadwal', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->delete(route('admin.schedules.mahasiswa.destroy', [$schedule, $mahasiswa]));

    $this->assertDatabaseMissing('schedule_mahasiswa', [
        'schedule_id' => $schedule->id,
        'user_id' => $mahasiswa->id,
    ]);
});

test('plot mahasiswa dihapus saat jadwal dihapus', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->delete(route('admin.schedules.destroy', $schedule));

    $this->assertDatabaseMissing('schedule_mahasiswa', ['schedule_id' => $schedule->id]);
});

test('halaman edit jadwal menampilkan mahasiswa yang sudah di-plot', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->get(route('admin.schedules.edit', $schedule))
        ->assertOk()
        ->assertSee('Plotting Mahasiswa')
        ->assertSee($mahasiswa->name)
        ->assertSee($mahasiswa->username);
});

test('halaman index jadwal menampilkan jumlah mahasiswa', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->get(route('admin.schedules.index'))
        ->assertOk()
        ->assertSee($schedule->nama_grup_sidang)
        ->assertSee('1');
});

test('halaman index jadwal menampilkan jenis sidang', function () {
    $admin = User::factory()->admin()->create();
    $jenis = JenisSidang::factory()->create(['nama' => 'TA']);
    $schedule = Schedule::factory()->create(['jenis_sidang_id' => $jenis->id]);

    $this->actingAs($admin)->get(route('admin.schedules.index'))
        ->assertOk()
        ->assertSee($schedule->nama_grup_sidang)
        ->assertSee('TA');
});

test('halaman edit jadwal menampilkan jenis sidang terpilih', function () {
    $admin = User::factory()->admin()->create();
    $jenis = JenisSidang::factory()->create(['nama' => 'KP']);
    $schedule = Schedule::factory()->create(['jenis_sidang_id' => $jenis->id]);

    $this->actingAs($admin)->get(route('admin.schedules.edit', $schedule))
        ->assertOk()
        ->assertSee('KP');
});

test('halaman create jadwal menampilkan searchable dosen component', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.schedules.create'))
        ->assertOk()
        ->assertSee('searchableSelect')
        ->assertSee('Ketik nama atau NIDN');
});

test('halaman edit jadwal menampilkan searchable dosen dan mahasiswa component', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);

    $this->actingAs($admin)->get(route('admin.schedules.edit', $schedule))
        ->assertOk()
        ->assertSee('searchableSelect')
        ->assertSee('Plot Mahasiswa')
        ->assertSee($dosen->name);
});

test('create jadwal dengan searchable component masih menyimpan dosen', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $jenis = JenisSidang::factory()->create();

    $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Test Group',
        'ruangan' => 'Ruang Test',
        'tanggal_sidang' => '2026-08-15',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ])->assertRedirect(route('admin.schedules.index'));

    $schedule = Schedule::where('nama_grup_sidang', 'Test Group')->first();
    $this->assertDatabaseHas('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $dosen->id,
    ]);
});

test('update jadwal dengan searchable component mengganti dosen', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $jenis = JenisSidang::factory()->create();
    $oldDosen = User::factory()->dosen()->create(['name' => 'Old Dosen']);
    $newDosen = User::factory()->dosen()->create(['name' => 'New Dosen']);
    $schedule->dosens()->attach($oldDosen->id);

    $this->actingAs($admin)->put(route('admin.schedules.update', $schedule), [
        'nama_grup_sidang' => $schedule->nama_grup_sidang,
        'ruangan' => $schedule->ruangan,
        'tanggal_sidang' => $schedule->tanggal_sidang->format('Y-m-d'),
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$newDosen->id],
    ])->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseMissing('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $oldDosen->id,
    ]);
    $this->assertDatabaseHas('schedule_dosen', [
        'schedule_id' => $schedule->id,
        'user_id' => $newDosen->id,
    ]);
});

test('search endpoint mengembalikan dosen yang cocok berdasarkan nama', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->dosen()->create(['name' => 'Budi Santoso', 'username' => '881101']);
    User::factory()->dosen()->create(['name' => 'Agus Wibowo', 'username' => '770202']);

    $this->actingAs($admin)
        ->get(route('admin.schedules.search-users', ['type' => 'dosen', 'term' => 'Budi']))
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJsonPath('data.0.name', 'Budi Santoso');
});

test('search endpoint mengembalikan dosen yang cocok berdasarkan NIDN', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->dosen()->create(['name' => 'Budi Santoso', 'username' => '12345678901']);
    User::factory()->dosen()->create(['name' => 'Agus Wibowo', 'username' => '98765432109']);

    $this->actingAs($admin)
        ->get(route('admin.schedules.search-users', ['type' => 'dosen', 'term' => '12345678901']))
        ->assertOk()
        ->assertJsonPath('data.0.username', '12345678901');
});

test('search endpoint mengembalikan mahasiswa yang cocok', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->mahasiswa()->create(['name' => 'Rina Marlina', 'username' => '221001']);
    User::factory()->mahasiswa()->create(['name' => 'Sukendi Pranoto', 'username' => '221002']);

    $this->actingAs($admin)
        ->get(route('admin.schedules.search-users', ['type' => 'mahasiswa', 'term' => 'Rina']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Rina Marlina');
});

test('search endpoint mengecualikan dosen yang sudah di-assign pada context edit', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $assigned = User::factory()->dosen()->create(['name' => 'Dosen Already Assigned']);
    $available = User::factory()->dosen()->create(['name' => 'Dosen Available']);
    $schedule->dosens()->attach($assigned->id);

    $res = $this->actingAs($admin)
        ->get("/admin/schedules/{$schedule->id}/search-users?type=dosen&term=Dosen")
        ->assertOk()
        ->json();

    $ids = collect($res['data'])->pluck('id')->toArray();
    expect($ids)->not->toContain($assigned->id);
    expect($ids)->toContain($available->id);
});

test('search endpoint mengecualikan mahasiswa yang sudah di-plot pada context edit', function () {
    $admin = User::factory()->admin()->create();
    $schedule = Schedule::factory()->create();
    $assigned = User::factory()->mahasiswa()->create(['name' => 'Mhs Already Plotted', 'username' => '220001']);
    $available = User::factory()->mahasiswa()->create(['name' => 'Mhs Available', 'username' => '220002']);
    $schedule->mahasiswas()->attach($assigned->id);

    $res = $this->actingAs($admin)
        ->get("/admin/schedules/{$schedule->id}/search-users?type=mahasiswa&term=Mhs")
        ->assertOk()
        ->json();

    $ids = collect($res['data'])->pluck('id')->toArray();
    expect($ids)->not->toContain($assigned->id);
    expect($ids)->toContain($available->id);
});

test('search endpoint menolak type yang tidak valid', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.schedules.search-users', ['type' => 'invalid']))
        ->assertStatus(422);
});

test('search endpoint memerlukan auth admin', function () {
    $this->get(route('admin.schedules.search-users', ['type' => 'dosen']))
        ->assertRedirect(route('login'));
});

test('search endpoint mengembalikan kosong saat term kosong', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->dosen()->create(['name' => 'Some Dosen', 'username' => '111']);

    $this->actingAs($admin)
        ->get(route('admin.schedules.search-users', ['type' => 'dosen', 'term' => '']))
        ->assertOk()
        ->assertJson(['data' => []]);
});
