<p align="center">
  <img src="docs/LOGO.png" width="140" alt="SIMSIDANG">
</p>

<h1 align="center">SIMSIDANG</h1>

<p align="center"><strong>Sistem Manajemen Sidang Akademik</strong> — digitalisasi alur sidang Tugas Akhir &amp; Kerja Praktek: dari upload laporan, pencatatan revisi pasca-sidang, hingga rekap penilaian.</p>

---

## Tentang Sistem Ini

SIMSIDANG mendigitalisasi proses revisi pasca-sidang akademik. Mahasiswa mengunggah laporan TA/KP, dosen memberikan catatan revisi per poin saat sidang, mahasiswa membalas dengan bukti perbaikan, dan dosen memverifikasi sampai titik revisi ditandai selesai (`resolved`). Admin mengelola data induk, jadwal sidang, rekap nilai, serta memantau kondisi sistem melalui **Asisten Virtual** berbasis LLM (read-only). buatan informatika undar

### Alur Kerja Utama

1. **Admin** membuat jadwal sidang, menetapkan dosen penguji, dan memasukkan mahasiswa ke grup sidang.
2. **Mahasiswa** mengunggah laporan (submission) terkait jadwalnya.
3. Setelah sidang, **Dosen** menulis catatan revisi per poin (`open`).
4. **Mahasiswa** merespons tiap poin revisi dengan mengunggah lampiran bukti perbaikan.
5. **Dosen** memverifikasi bukti, lalu menandai poin revisi sebagai `resolved`.
6. **Dosen** mengisi penilaian sesuai template prodi; hasil dapat dicetak (PDF) dan direkap oleh admin (Excel/PDF).

## Fitur

### Umum (semua role)
- Dashboard sesuai role (mahasiswa / dosen / admin)
- Notifikasi in-app dengan badge jumlah belum dibaca
- Unduh file yang aman — submission & lampiran disimpan di disk privat (`FILESYSTEM_DISK=local`), hanya bisa diakses lewat route terproteksi
- Login menggunakan **username (NIM/NIDN)**, bukan email (via Laravel Fortify)

### Mahasiswa
- Upload submission laporan sidang (PDF ≤ 10 MB)
- Pantau status submission & daftar poin revisi
- Upload lampiran bukti perbaikan per poin revisi (.pdf/.docx/.jpeg/.png ≤ 5 MB)
- Lihat dan cetak hasil penilaian (PDF)

### Dosen
- Daftar & detail submission mahasiswa
- **AI-read**: ekstraksi otomatis isi PDF laporan untuk membantu pembacaan awal
- Catatan revisi per poin: simpan draft, publikasikan, tandai `resolved`
- Penilaian berbasis template assessment per prodi/jenis sidang + cetak PDF

### Admin
- CRUD data induk: pengguna, fakultas, program studi, jenis sidang, template penilaian
- Manajemen jadwal sidang: kelompok sidang, ruangan, waktu, import massal via Excel (+ unduhan template)
- Penempatan dosen pembimbing pada tiap submission
- Rekap nilai seluruh sidang + export Excel/PDF
- **Asisten Virtual (FR-05)**: chat berbasis LLM dengan tool-calling *read-only* untuk query data agregat (rate-limited, tanpa akses tulis ke database)

## Teknologi

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.4+, MySQL 8 |
| Auth | Laravel Fortify (login `username`, Gate RBAC: `mahasiswa` / `dosen` / `admin`) |
| Frontend | Tailwind CSS v4, Alpine.js, ApexCharts — dibundel via Vite |
| PDF / Excel | barryvdh/laravel-dompdf, maatwebsite/excel |
| Parsing PDF | smalot/pdfparser (fitur AI-read) |
| Testing | Pest |

## Instalasi

### Prasyarat
- PHP 8.4+ (ekstensi: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`)
- Composer
- Node.js 18+ & npm
- MySQL 8.x

### Langkah

```bash
# 1. Clone repo
git clone <repo-url> simsidang
cd simsidang

# 2. Install dependency
composer install
npm install

# 3. Siapkan environment
cp .env.example .env
php artisan key:generate

# 4. Buat database, lalu sesuaikan kredensial di .env
mysql -u root -e "CREATE DATABASE simsidang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# edit .env → DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Migrasi + data awal
php artisan migrate --seed

# 6. Pastikan folder storage writable
chmod -R 775 storage bootstrap/cache
```

### Akun Demo (hasil seeder)

| Role | Username | Password |
|---|---|---|
| Admin | `telo` | `kaspe` |
| Dosen | `00110200001` – `00110200004` | `password` |
| Mahasiswa | `20200101001` – `20200101006` | `password` |

> Seeder juga membuat contoh fakultas, prodi, template penilaian, jadwal sidang, submission, dan poin revisi agar alur bisa langsung dicoba.

## Menjalankan Aplikasi

### Mode Development

Jalankan dua proses paralel (dua terminal terpisah):

```bash
php artisan serve    # Terminal 1 — API/web server
npm run dev          # Terminal 2 — Vite HMR (CSS & JS)
```

Atau satu perintah saja (server + queue + log tail + vite sekaligus):

```bash
composer dev
```

Akses aplikasi di `http://localhost:8000`.

### Build Produksi

```bash
npm run build
php artisan optimize
```

> Dependensi production sudah lengkap — `composer install --no-dev` aman digunakan saat deploy.

## Konfigurasi `.env` Penting

| Variabel | Fungsi |
|---|---|
| `DB_*` | Koneksi MySQL 8 |
| `FILESYSTEM_DISK=local` | File submission disimpan privat (jangan diubah ke `public`) |
| `ASSISTANT_LLM_URL` / `_API_KEY` / `_MODEL` | Endpoint LLM kompatibel OpenAI (OpenAI, Ollama, LM Studio, vLLM) untuk Asisten Virtual FR-05 |
| `ASSISTANT_RATE_PER_MINUTE` / `_PER_CONVERSATION` | Batas rate chat asisten |
| `UNIVERSITY_*` | Identitas kampus pada kop PDF penilaian |
| `NILAI_DOSPEM_WEIGHT` / `NILAI_PENGUJI_WEIGHT` | Bobot rekap nilai (total harus 100) |

## Testing

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature
```

> **Peringatan:** suite Feature memakai `RefreshDatabase` dan akan **mengosongkan database** yang dikonfigurasi di `.env`. Backup dulu, lalu seed ulang setelahnya:

```bash
mysqldump -u<user> -p <database> > backup.sql      # sebelum test
php artisan migrate:fresh --seed                    # pulihkan setelah test
```

## Kualitas Kode

```bash
npm run lint           # ESLint (resources/js)
./vendor/bin/pint      # Laravel Pint — PSR-12
```

## Struktur Proyek Singkat

```
app/
  Http/Controllers/     # dikelompokkan per role: Mahasiswa/, Dosen/, Admin/
  Http/Requests/        # validasi form terpusat
  Models/
resources/
  css/app.css           # Tailwind v4 (@theme brand indigo #6366f1)
  js/components/        # komponen Alpine.js
  views/                # Blade templates
routes/web.php
docs/                   # dokumentasi lengkap project
```

## Dokumentasi Lanjutan

| Dokumen | Isi |
|---|---|
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | Prioritas fitur per tahap (Tahap 1 → 3) |
| [`docs/SCHEMA.md`](docs/SCHEMA.md) | Skema database & urutan migrasi |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Struktur folder & alur request |
| [`docs/FRONTEND-GUIDE.md`](docs/FRONTEND-GUIDE.md) | Checklist porting template → Blade |
| [`docs/CODING-STANDARDS.md`](docs/CODING-STANDARDS.md) | PSR-12, struktur test, format commit |
| [`docs/GLOSSARY.md`](docs/GLOSSARY.md) | Istilah domain → penamaan kode |
| [`docs/SETUP.md`](docs/SETUP.md) | Detail setup environment |
| [`docs/MEMORY.md`](docs/MEMORY.md) | Log keputusan & histori pengerjaan |
