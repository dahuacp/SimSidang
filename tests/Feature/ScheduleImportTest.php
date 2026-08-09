<?php

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
