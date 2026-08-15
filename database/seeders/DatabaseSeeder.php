<?php

namespace Database\Seeders;

use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
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
        $prodis = collect();
        $prodiData = [
            ['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika'],
            ['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi'],
            ['kode_prodi' => 'DKV', 'nama_prodi' => 'Desain Komunikasi Visual'],
            ['kode_prodi' => 'MBTI', 'nama_prodi' => 'Manajemen Bisnis dan Teknologi Informasi'],
            ['kode_prodi' => 'ARS', 'nama_prodi' => 'Arsitektur'],
        ];

        foreach ($prodiData as $data) {
            $prodis->push(Prodi::create([
                'kode_prodi' => $data['kode_prodi'],
                'nama_prodi' => $data['nama_prodi'],
            ]));
        }

        $jenisSidangs = [];
        foreach (['TA' => 'Tugas Akhir', 'KP' => 'Kerja Praktek', 'Milestone Design' => 'Fase perencanaan dan desain'] as $nama => $deskripsi) {
            $jenisSidangs[$nama] = JenisSidang::create(['nama' => $nama, 'deskripsi' => $deskripsi]);
        }

        foreach ($prodis as $prodi) {
            foreach ($jenisSidangs as $jenis) {
                AssessmentTemplate::create([
                    'prodi_id' => $prodi->id,
                    'jenis_sidang_id' => $jenis->id,
                    'nama' => 'Template '.$prodi->nama_prodi.' — '.$jenis->nama,
                    'nilai_penyebut' => 15,
                    'nilai_pengali' => 100,
                    'items' => [
                        ['name' => 'Kualitas Laporan', 'maksimal' => 5, 'urutan' => 1],
                        ['name' => 'Penguasaan Materi', 'maksimal' => 5, 'urutan' => 2],
                        ['name' => 'Presentasi dan Komunikasi', 'maksimal' => 5, 'urutan' => 3],
                    ],
                ]);
            }
        }

        $admin = User::create([
            'name' => 'Admin SISIDANG',
            'username' => 'telo',
            'email' => 'admin@simsidang.local',
            'password' => Hash::make('kaspe'),
            'role' => 'admin',
            'prodi_id' => null,
        ]);

        $dosens = collect();
        $dosenNames = [
            'Dr. Budi Santoso, M.Kom.' => 'TI',
            'Dra. Siti Rahayu, M.T.' => 'SI',
            'Ir. Agus Wijaya, M.Sc.' => 'TI',
            'Dr. Dewi Lestari, S.Kom., M.Kom.' => 'SI',
        ];

        $dosenNo = 0;
        foreach ($dosenNames as $name => $prodiKode) {
            $dosenNo++;
            $dosens->push(User::create([
                'name' => $name,
                'username' => '001102'.str_pad((string) $dosenNo, 5, '0', STR_PAD_LEFT),
                'email' => 'dosen'.$dosenNo.'@simsidang.local',
                'password' => Hash::make('password'),
                'role' => 'dosen',
                'prodi_id' => $prodis->firstWhere('kode_prodi', $prodiKode)->id,
            ]));
        }

        $mahasiswaData = [
            ['name' => 'Andi Pratama', 'nim' => '20200101001', 'judul' => 'Sistem Informasi Manajemen Aset Berbasis Web', 'prodi_kode' => 'TI'],
            ['name' => 'Bella Anggraini', 'nim' => '20200101002', 'judul' => 'Aplikasi E-Learning dengan Fitur Gamifikasi', 'prodi_kode' => 'TI'],
            ['name' => 'Citra Dewi', 'nim' => '20200101003', 'judul' => 'Sistem Rekomendasi Pemilihan Jurusan Menggunakan Metode TOPSIS', 'prodi_kode' => 'SI'],
            ['name' => 'Dimas Saputra', 'nim' => '20200101004', 'judul' => 'Implementasi IoT untuk Monitoring Tanaman Hidroponik', 'prodi_kode' => 'SI'],
            ['name' => 'Eka Ramadhani', 'nim' => '20200101005', 'judul' => 'Sistem Pendukung Keputusan Seleksi Beasiswa', 'prodi_kode' => 'DKV'],
            ['name' => 'Fajar Nugroho', 'nim' => '20200101006', 'judul' => 'Aplikasi Booking Lapangan Olahraga Berbasis Mobile', 'prodi_kode' => 'TI'],
        ];

        $mahasiswa = collect();
        foreach ($mahasiswaData as $data) {
            $prodi = $prodis->firstWhere('kode_prodi', $data['prodi_kode']);
            $mahasiswa->push(User::create([
                'name' => $data['name'],
                'username' => $data['nim'],
                'email' => $data['nim'].'@mahasiswa.local',
                'password' => Hash::make('password'),
                'role' => 'mahasiswa',
                'prodi_id' => $prodi->id,
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

        $scheduleJenis = [0 => 'TA', 1 => 'TA', 2 => 'KP', 3 => 'TA'];
        foreach ($scheduleJenis as $idx => $jn) {
            $schedules[$idx]->update(['jenis_sidang_id' => $jenisSidangs[$jn]->id]);
        }

        $dospemByProdi = [
            'TI' => [$dosens[0]->id, $dosens[2]->id],
            'SI' => [$dosens[1]->id, $dosens[3]->id],
            'DKV' => [$dosens[0]->id],
        ];
        foreach ($mahasiswa as $idx => $mhs) {
            $mhs->dosenPembimbing()->attach($dospemByProdi[$mahasiswaData[$idx]['prodi_kode']]);
        }

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
