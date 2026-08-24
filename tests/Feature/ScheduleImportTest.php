<?php

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('admin dapat mengimport jadwal dari CSV', function () {
    $admin = User::factory()->admin()->create();

    $csv = "nama_grup_sidang,ruangan,tanggal_sidang,jam_mulai,jam_selesai,dosen_ids\n";
    $csv .= "Grup Test,Ruang A,2026-08-20,09:00,11:00,\n";
    $csv .= "Grup B,Ruang B,2026-08-21,10:00,12:00,\n";

    $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

    $response = $this->actingAs($admin)->post(route('admin.schedules.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseHas('schedules', ['nama_grup_sidang' => 'Grup Test', 'ruangan' => 'Ruang A']);
    $this->assertDatabaseHas('schedules', ['nama_grup_sidang' => 'Grup B']);
});

test('import: baris bentrok dengan jadwal database masuk failures', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $existing = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Pagi',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $existing->dosens()->attach($dosen->id);

    $csv = "nama_grup_sidang,ruangan,tanggal_sidang,jam_mulai,jam_selesai,dosen_ids\n";
    $csv .= "Grup Bentrok,Ruang A,2026-08-20,10:00,12:00,{$dosen->id}\n";
    $csv .= "Grup Aman,Ruang B,2026-08-21,09:00,11:00,\n";

    $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

    $response = $this->actingAs($admin)->post(route('admin.schedules.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('schedules', ['nama_grup_sidang' => 'Grup Aman']);
    $this->assertDatabaseMissing('schedules', ['nama_grup_sidang' => 'Grup Bentrok']);
    expect(session('error'))->toContain('Baris 2')->toContain('Grup Pagi');
});

test('import: dua baris dalam satu file saling bentrok', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();

    $csv = "nama_grup_sidang,ruangan,tanggal_sidang,jam_mulai,jam_selesai,dosen_ids\n";
    $csv .= "Grup Satu,Ruang A,2026-08-20,09:00,11:00,{$dosen->id}\n";
    $csv .= "Grup Dua,Ruang B,2026-08-20,10:00,12:00,{$dosen->id}\n";

    $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

    $response = $this->actingAs($admin)->post(route('admin.schedules.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('schedules', ['nama_grup_sidang' => 'Grup Satu']);
    $this->assertDatabaseMissing('schedules', ['nama_grup_sidang' => 'Grup Dua']);
    expect(session('error'))->toContain('Baris 3');
});
