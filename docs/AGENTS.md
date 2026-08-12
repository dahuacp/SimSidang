# AGENTS.md — SIMSIDANG (Sistem Manajemen Sidang Akademik)

> **Catatan:** Versi kanonik AGENTS.md yang dibaca otomatis oleh OpenCode ada di **root repo** (`/AGENTS.md`), berbahasa Inggris, lebih ringkas. File ini (`docs/AGENTS.md`) adalah versi rinci berbahasa Indonesia — pelengkap bila perlu konteks tambahan.

Dokumen pendukung lain (baca sesuai kebutuhan task, tidak semua perlu dibaca setiap sesi):
- `PRD-SIMSIDANG-v2.md` — requirement produk lengkap (FR-01 s/d FR-05, journey, dsb).
- `ARCHITECTURE.md` — struktur folder & bagaimana Laravel + template Metis nyambung.
- `SCHEMA.md` — skema database murni (tabel, kolom, relasi).
- `FRONTEND-GUIDE.md` — panduan porting template Metis ke Blade.
- `ROADMAP.md` — checklist fitur per tahap MVP.
- `CODING-STANDARDS.md` — konvensi kode Laravel.
- `GLOSSARY.md` — pemetaan istilah domain (Indonesia) ke penamaan kode (Inggris).
- `SETUP.md` — instalasi & environment lokal.
- `MEMORY.md` — **vault memory project, lihat protokol wajib di bawah.**

---

## 1. Ringkasan Proyek
SIMSIDANG mendigitalisasi proses revisi pasca-sidang (Tugas Akhir/Kerja Praktek). Mahasiswa upload laporan, dosen kasih catatan revisi per poin saat sidang, mahasiswa balas + lampirkan bukti perbaikan, dosen tandai `resolved`. Admin punya asisten virtual (chat berbasis LLM, read-only, tool-calling) untuk memantau kondisi mahasiswa/dosen/revisi.

## 2. Tech Stack
- **Backend:** Laravel 13.x, PHP 8.4+
- **Auth:** Laravel Fortify (login via `username` = NIM/NIDN, bukan email), Gate untuk RBAC (`mahasiswa`, `dosen`, `admin`)
- **Database:** MySQL 8.x
- **Frontend:** Template TailAdmin (Tailwind CSS v4 + Alpine.js + ApexCharts), diintegrasikan via `laravel-vite-plugin` — BUKAN dipakai sebagai aset statis terpisah. Tanpa `tailwind.config.js`; styling di `resources/css/app.css`
- **Realtime/polling:** Livewire polling (bukan websocket) untuk notifikasi & chat asisten
- **Warna aksen:** Indigo (`brand-500: #6366f1`), lihat `FRONTEND-GUIDE.md`

## 3. Command Penting
```bash
# Setup awal
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Development (jalankan dua proses paralel)
php artisan serve
npm run dev

# Testing
php artisan test           # atau: ./vendor/bin/pest
npm run lint
npm run build               # build produksi aset Vite

# Sebelum menandai task selesai, WAJIB jalankan:
php artisan test
npm run lint
```

## 4. Struktur Folder (ringkas — detail di ARCHITECTURE.md)
```
app/
  Models/            # Eloquent models
  Http/Controllers/  # Controllers per role (Mahasiswa/, Dosen/, Admin/)
  Http/Requests/     # Form Request untuk validasi terpusat
  Policies/ Gates/   # RBAC
resources/
  views/             # Blade, hasil porting dari template TailAdmin
  css/app.css        # Tailwind v4 (entry + @theme + @utility), porting dari TailAdmin
  js/                # Alpine.js components hasil porting TailAdmin
database/migrations/
routes/web.php
```

## 5. Aturan Kerja untuk Agent
- **Jangan** menaruh logic bisnis di Blade/Controller langsung tanpa Form Request untuk input yang butuh validasi file (lihat FR-02, FR-04 di PRD).
- **Jangan** memberi asisten virtual (FR-05) akses tulis ke database. Semua tool asisten harus read-only — lihat daftar tool yang diizinkan di PRD bagian FR-05.
- Saat porting halaman dari `src-modern/*.html` (template Metis) ke Blade, jangan copy-paste mentah — ikuti checklist di `FRONTEND-GUIDE.md`.
- Semua istilah domain (mahasiswa, dosen, ruang sidang, poin revisi) harus konsisten dengan `GLOSSARY.md` saat dipetakan ke nama tabel/kolom/kelas.
- Kerjakan fitur sesuai urutan prioritas di `ROADMAP.md` (Tahap 1 dulu) kecuali diminta lain oleh user.
- Ikuti `CODING-STANDARDS.md` untuk gaya kode, penamaan migration/model, dan struktur test.

---

## 6. 🔒 Protokol Memory Vault (WAJIB — jangan dilewatkan)

Project ini punya file `MEMORY.md` yang berfungsi sebagai **vault memory** lintas sesi. OpenCode tidak otomatis mengingat sesi sebelumnya, jadi `MEMORY.md` adalah satu-satunya cara agent di sesi berikutnya tahu apa yang sudah terjadi, apa yang sedang dikerjakan, dan keputusan apa yang sudah diambil.

**Aturan wajib:**
1. **Di awal sesi**, baca `MEMORY.md` terlebih dahulu sebelum mulai kerja, untuk tahu konteks terakhir.
2. **Setiap kali** kamu (agent) menyelesaikan task, membuat keputusan teknis penting (mis. memilih pendekatan implementasi, mengubah skema, deviasi dari PRD), atau menemukan hal yang perlu diingat sesi berikutnya (bug, workaround, TODO) — **update `MEMORY.md` sebelum sesi berakhir**. Jangan tunggu diminta.
3. **Jangan menimpa (overwrite) riwayat lama.** `MEMORY.md` bersifat *append-only* pada bagian "Log Sesi" — tambahkan entri baru di atas (paling baru di atas), jangan hapus entri lama kecuali sudah tidak relevan sama sekali dan itu pun harus disebutkan alasannya.
4. Bagian "Status Saat Ini" di `MEMORY.md` **boleh** ditimpa/diperbarui (bukan append) karena itu snapshot kondisi terkini, bukan log historis.
5. Format entri log: tanggal, ringkasan task, keputusan penting, file yang diubah, dan hal yang perlu diperhatikan sesi berikutnya.
6. Jika kamu ragu apakah sesuatu cukup penting untuk dicatat — catat saja. Lebih baik `MEMORY.md` sedikit verbose daripada sesi berikutnya kehilangan konteks.

Tujuan protokol ini: siapa pun (manusia atau agent lain) yang membuka project ini bisa langsung paham histori dan status project cukup dari membaca `MEMORY.md`, tanpa harus menelusuri ulang seluruh git log atau menebak-nebak.
