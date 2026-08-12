# SETUP.md — Instalasi Lokal SIMSIDANG

## Prasyarat
- PHP 8.4+
- Composer
- Node.js 18+ & npm
- MySQL 8.x
- Git

## Langkah Instalasi

```bash
# 1. Clone repo
git clone <repo-url> simsidang
cd simsidang

# 2. Install dependency backend
composer install

# 3. Install dependency frontend
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate
```

## Konfigurasi `.env` yang Wajib Diisi

```env
APP_NAME=SIMSIDANG
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simsidang
DB_USERNAME=root
DB_PASSWORD=

# Untuk FR-05 — Asisten Virtual Admin (bisa dikosongkan di Tahap 1, baru dibutuhkan di Tahap 3)
ASSISTANT_LLM_BASE_URL=
ASSISTANT_LLM_MODEL=
ASSISTANT_LLM_API_KEY=

# Filesystem — submission disimpan di disk lokal, bukan public
FILESYSTEM_DISK=local
```

## Migration & Seeder

```bash
php artisan migrate --seed
```

Seeder awal (`database/seeders/DatabaseSeeder.php`) sebaiknya membuat:
- 1 user `admin`
- Beberapa user `dosen` dengan NIDN dummy
- Beberapa user `mahasiswa` dengan NIM dummy
- Beberapa `schedules` contoh + relasi `schedule_dosen`

## Menjalankan Development Server

Jalankan dua proses secara paralel (dua terminal terpisah):

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (HMR untuk resources/css & resources/js)
npm run dev
```

Akses di `http://localhost:8000`.

## Build Produksi

```bash
npm run build
php artisan optimize
```

## Menjalankan Test

```bash
php artisan test
# atau
./vendor/bin/pest
```

## Troubleshooting Umum
- **Aset CSS/JS tidak muncul:** pastikan `npm run dev` masih berjalan di terminal terpisah, atau jalankan `npm run build` untuk mode produksi.
- **Error upload file:** cek `FILESYSTEM_DISK=local` di `.env` dan pastikan `storage/app` writable (`chmod -R 775 storage`).
- **Login gagal terus:** pastikan Fortify sudah dikonfigurasi login via `username`, bukan default `email` — cek `config/fortify.php`.
