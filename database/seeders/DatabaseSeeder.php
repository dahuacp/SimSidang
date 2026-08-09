<?php

namespace Database\Seeders;

use App\Models\RevisionNote;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin SISIDANG',
            'username' => 'telo',
            'email' => 'admin@simsidang.local',
            'password' => Hash::make('kaspe'),
            'role' => 'admin',
        ]);

        $dosens = collect();
        $dosenNames = [
            'Dr. Budi Santoso, M.Kom.',
            'Dra. Siti Rahayu, M.T.',
            'Ir. Agus Wijaya, M.Sc.',
            'Dr. Dewi Lestari, S.Kom., M.Kom.',
        ];

        foreach ($dosenNames as $i => $name) {
            $dosens->push(User::create([
                'name' => $name,
                'username' => '001102'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'email' => 'dosen'.($i + 1).'@simsidang.local',
                'password' => Hash::make('password'),
                'role' => 'dosen',
            ]));
        }

        $mahasiswaData = [
            ['name' => 'Andi Pratama', 'nim' => '20200101001', 'judul' => 'Sistem Informasi Manajemen Aset Berbasis Web'],
            ['name' => 'Bella Anggraini', 'nim' => '20200101002', 'judul' => 'Aplikasi E-Learning dengan Fitur Gamifikasi'],
            ['name' => 'Citra Dewi', 'nim' => '20200101003', 'judul' => 'Sistem Rekomendasi Pemilihan Jurusan Menggunakan Metode TOPSIS'],
            ['name' => 'Dimas Saputra', 'nim' => '20200101004', 'judul' => 'Implementasi IoT untuk Monitoring Tanaman Hidroponik'],
            ['name' => 'Eka Ramadhani', 'nim' => '20200101005', 'judul' => 'Sistem Pendukung Keputusan Seleksi Beasiswa'],
            ['name' => 'Fajar Nugroho', 'nim' => '20200101006', 'judul' => 'Aplikasi Booking Lapangan Olahraga Berbasis Mobile'],
        ];

        $mahasiswa = collect();
        foreach ($mahasiswaData as $data) {
            $mahasiswa->push(User::create([
                'name' => $data['name'],
                'username' => $data['nim'],
                'email' => $data['nim'].'@mahasiswa.local',
                'password' => Hash::make('password'),
                'role' => 'mahasiswa',
            ]));
        }

        $today = now()->toDateString();

        $schedules = collect();
        $groups = [
            ['nama_grup_sidang' => 'Sidang TA Gelombang 1', 'ruangan' => 'Ruang Lab Komputer 3', 'tanggal' => $today, 'mulai' => '08:00', 'selesai' => '10:00'],
            ['nama_grup_sidang' => 'Sidang TA Gelombang 2', 'ruangan' => 'Ruang Seminar 2', 'tanggal' => $today, 'mulai' => '10:30', 'selesai' => '12:30'],
            ['nama_grup_sidang' => 'Sidang KP Gelombang 1', 'ruangan' => 'Ruang Lab Komputer 3', 'tanggal' => $today, 'mulai' => '13:30', 'selesai' => '15:30'],
            ['nama_grup_sidang' => 'Sidang TA Gelombang 3', 'ruangan' => 'Ruang Sidang A', 'tanggal' => now()->addDays(3)->toDateString(), 'mulai' => '08:00', 'selesai' => '10:00'],
        ];

        foreach ($groups as $i => $g) {
            $schedules->push(Schedule::create([
                'nama_grup_sidang' => $g['nama_grup_sidang'],
                'ruangan' => $g['ruangan'],
                'tanggal_sidang' => $g['tanggal'],
                'jam_mulai' => $g['mulai'],
                'jam_selesai' => $g['selesai'],
            ]));
        }

        $schedules[0]->dosens()->attach([$dosens[0]->id, $dosens[1]->id]);
        $schedules[1]->dosens()->attach([$dosens[2]->id, $dosens[3]->id]);
        $schedules[2]->dosens()->attach([$dosens[0]->id, $dosens[2]->id]);
        $schedules[3]->dosens()->attach([$dosens[1]->id, $dosens[3]->id]);

        $submissions = collect();
        foreach ($mahasiswa as $idx => $mhs) {
            $submissions->push(Submission::create([
                'user_id' => $mhs->id,
                'schedule_id' => $schedules[$idx % 3]->id,
                'judul_laporan' => $mahasiswaData[$idx]['judul'],
                'file_path' => null,
                'status' => 'pending',
            ]));
        }

        $submission = $submissions[0];
        $submission->update(['status' => 'revisi']);
        RevisionNote::create([
            'submission_id' => $submission->id,
            'dosen_id' => $dosens[0]->id,
            'catatan_revisi' => 'Perbaiki penulisan referensi sesuai format APA dan lengkapi analisis pengujian sistem.',
            'status_poin' => 'open',
        ]);
        RevisionNote::create([
            'submission_id' => $submission->id,
            'dosen_id' => $dosens[1]->id,
            'catatan_revisi' => 'Tambah dokumentasi diagram arsitektur sistem pada bab 3.',
            'status_poin' => 'open',
        ]);
    }
}
