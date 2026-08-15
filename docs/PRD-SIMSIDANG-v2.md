# Product Requirement Document (PRD)

## Project Name: Sistem Manajemen Sidang Akademik (SIMSIDANG)
**Author:** Senior Laravel Developer
**Status:** Updated v3 (Laravel 13, MySQL, Metis Admin Template — Yellow Accent, Asisten Virtual Admin menggantikan Analisa AI Dokumen)
**Target Release:** Q3 2026

---

## 1. Objective & Product Overview
Aplikasi ini bertujuan untuk mendigitalisasi proses pasca-sidang dan pelaksanaan sidang (Tugas Akhir, Kerja Praktek, dll). Sistem ini menjembatani mahasiswa untuk mengelola revisi secara transparan dan dosen penguji untuk mengakses berkas laporan berdasarkan ruang atau grouping jadwal sidang secara real-time.

### Target Audiences
*   **Mahasiswa:** Mengunggah draf laporan, melihat catatan revisi dari penguji, dan mengunggah bukti perbaikan.
*   **Dosen Penguji:** Memeriksa laporan mahasiswa sesuai ruang/jadwal sidang yang ditugaskan dan memberikan catatan revisi terstruktur.

---

## 2. User Personas & User Journeys

### 2.1 Mahasiswa Journey
1. Login menggunakan NIM.
2. Mengunggah dokumen laporan awal (PDF) ke sistem.
3. Melaksanakan sidang, lalu memantau *input* revisi dari Dosen Penguji melalui dashboard.
4. Menerima daftar poin revisi (*Revision Notes*).
5. Merespons tiap poin revisi dengan menuliskan penjelasan dan melampirkan (*attach*) bukti gambar/dokumen.
6. Menunggu status tiap poin berubah menjadi *Resolved* (Disetujui Dosen).

### 2.2 Dosen Penguji Journey
1. Login menggunakan NIDN.
2. Masuk ke halaman dashboard utama yang otomatis memfilter daftar mahasiswa berdasarkan **Ruang Sidang** dan **Grouping Jadwal** pada hari berjalan.
3. Membuka detail mahasiswa untuk membaca/mengunduh dokumen laporan saat sidang.
4. Memberikan catatan revisi per poin secara spesifik ke mahasiswa.
5. Memeriksa kembali balasan revisi dari mahasiswa (melihat teks penjelasan & attachment), lalu mengubah status poin menjadi *Resolved*.

---

## 3. Functional Requirements (Fitur Utama)

### FR-01: Autentikasi & Manajemen Pengguna
*   **Kebutuhan:** Login multi-role (Mahasiswa & Dosen).
*   **Spesifikasi Laravel 13:**
    *   Menggunakan **Laravel Fortify** (bukan Livewire starter kit) untuk backend auth — login via `username` (NIM/NIDN).
    *   Pemisahan hak akses (*Role-based Access Control*) menggunakan Laravel Gate di `AppServiceProvider::configureGates()`.
    *   *Security check*: Rate-limiting login bawaan Fortify untuk mencegah brute force.
    *   Passkey authentication tersedia melalui Fortify + tabel `passkeys`.
*   **Spesifikasi UI:** Halaman login/register memakai layout auth kosong (blank layout) dari template Metis, tanpa sidebar, dengan aksen warna kuning pada tombol submit dan logo area.

### FR-02: Upload Data Laporan (Mahasiswa)
*   **Kebutuhan:** Mahasiswa mengunggah berkas laporan utama sebelum sidang.
*   **Spesifikasi Teknik:**
    *   Format file wajib `.pdf` dengan batas maksimal ukuran file 10MB.
    *   Penyimpanan menggunakan `Storage::disk('local')` (di luar direktori publik) untuk menjaga kerahasiaan data sebelum dipublikasikan.
    *   File diakses oleh dosen menggunakan mekanisme *Stream/Download Response* yang aman melalui Controller terlindungi.
*   **Spesifikasi UI:** Komponen upload memakai pola *File Manager*/dropzone bawaan template Metis (ikon Bootstrap Icons, progress bar aksen kuning).

### FR-03: Dashboard Ruang & Jadwal Sidang (Dosen)
*   **Kebutuhan:** Dosen melihat mahasiswa sesuai ruangan dan *grouping* jadwal.
*   **Spesifikasi Teknik:**
    *   Halaman dashboard menyajikan data mahasiswa yang difilter secara *default* berdasarkan tanggal hari ini dan `ruang_id` di mana dosen tersebut bertugas.
    *   Optimasi *query* MySQL menggunakan eager loading (`with('student')`) untuk menghindari masalah N+1 query saat menampilkan daftar mahasiswa di ruangan tersebut.
*   **Spesifikasi UI:** Memakai halaman *Analytics Dashboard* Metis sebagai basis (KPI cards + data table), dengan badge status (`open`/`resolved`) memakai warna kuning (pending/open) dan hijau (resolved) agar kontras.

### FR-04: Manajemen Poin Revisi & Attachment (Dosen & Mahasiswa)
*   **Kebutuhan:** Dosen memberikan note per poin, Mahasiswa menjawab dan melampirkan dokumen pendukung per poin.
*   **Spesifikasi Teknik:**
    *   Sistem mencatat revisi secara granular (per item catatan).
    *   Mahasiswa diizinkan mengunggah dokumen pendukung (Format: `.pdf`, `.docx`, `.jpeg`, `.png`, max 5MB per file).
    *   Pemanfaatan fitur *File Validation* terpusat pada Form Request Laravel 13.
*   **Spesifikasi UI:** Pola *timeline/chat* dari halaman *Messages* template Metis diadaptasi untuk menampilkan percakapan revisi per poin (catatan dosen vs balasan mahasiswa), dengan bubble aktif memakai aksen kuning.

---

## 4. Technical Architecture & Design System

### 4.1 Tech Stack
*   **Backend Framework:** Laravel 13.x (PHP 8.4+), Laravel Fortify untuk autentikasi.
*   **Database:** MySQL 8.x (memanfaatkan fitur JSON columns bila dibutuhkan untuk fleksibilitas log/metadata).
*   **Frontend Template:** [Metis — Bootstrap Admin Template](https://github.com/puikinsh/Bootstrap-Admin-Template) oleh Colorlib/puikinsh.
    *   Bootstrap 5.3.8, Alpine.js 3.x (interaksi ringan pengganti jQuery), Bootstrap Icons, ApexCharts (untuk grafik ringkasan insight dari Asisten Virtual Admin di FR-05).
    *   Dibangun di atas Vite + SCSS — cocok diintegrasikan ke pipeline **Laravel Vite Plugin** (`resources/js`, `resources/scss`), bukan dipakai sebagai aset statis terpisah.
    *   Zero third-party runtime request (font, chart, ikon di-self-host) — selaras dengan kebutuhan keamanan data akademik (tidak ada kebocoran IP pengguna ke CDN eksternal).

### 4.2 Integrasi Template Metis ke Laravel
*   **Struktur Aset:**
    *   Salin `src-modern/styles/scss/*` → `resources/scss/` dan `src-modern/scripts/*` → `resources/js/`.
    *   Konfigurasi ulang `vite.config.js` project menjadi `vite.config.js` Laravel standar (`laravel-vite-plugin`), dengan entry `resources/scss/app.scss` dan `resources/js/app.js`.
*   **Konversi Halaman:**
    *   Setiap file `.html` statis pada `src-modern/*.html` dikonversi menjadi Blade layout (`resources/views/layouts/app.blade.php`) dan partial (`sidebar`, `topbar`, `footer`) agar dapat dipakai ulang di semua halaman dashboard Mahasiswa & Dosen.
    *   Komponen Alpine.js (`components/*.js`) tetap dipakai apa adanya untuk interaksi client-side (dropdown, modal, toggle dark/light mode dinonaktifkan atau disesuaikan kebutuhan, search component, dsb).
*   **Role-based Layout:** Sidebar menu di-generate dinamis dari Blade berdasarkan `role` user (mahasiswa/dosen/admin) via Gate, bukan hard-coded seperti pada template asli.

### 4.3 Theming — Aksen Warna Kuning
Override variabel SCSS pada `resources/scss/abstracts/_variables.scss` (hasil salinan dari `_variables.scss` template asli):

```scss
// Brand Colors — SIMSIDANG (aksen kuning)
$primary:   #F5B400;   // Kuning utama — tombol, active state, sidebar highlight
$secondary: #64748b;   // Tetap abu-abu netral bawaan template
$success:   #10b981;   // Dipakai khusus untuk status "Resolved"
$warning:   #F5B400;   // Disamakan dengan primary agar konsisten
$info:      #0ea5e9;

// Typography — tetap memakai default template
$font-family-sans-serif: "Inter", system-ui, sans-serif;
$font-size-base: 0.9rem;

// Spacing & Layout — tetap default template
$border-radius: 0.75rem;
$box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
```

*   Pastikan kontras teks di atas tombol/badge kuning tetap memenuhi standar aksesibilitas (gunakan teks gelap `#1e2327`, bukan putih, di atas elemen `$primary`).
*   Logo/branding area pada sidebar (`.sidebar-brand`) diganti dengan logo institusi + teks "SIMSIDANG", warna aksen kuning pada garis bawah aktif menu.
*   Dark mode bawaan template tetap dipertahankan opsional (toggle di topbar), namun palet kuning disesuaikan agar tetap kontras pada mode gelap (`$primary-dark: #FFC93C`).

### 4.4 Database Schema (MySQL Migration Architecture)

Tables `users`, `schedules`, `submissions`, `revision_notes`, `revision_attachments`, `schedule_dosen` (pivot).

```php
// 1. Tabel Users — kombinasi Admin/Dosen/Mahasiswa + Fortify fields
//    username & role ditambahkan via migration terpisah
Schema::table('users', function (Blueprint $table) {
    $table->string('username')->unique()->after('id');     // NIM (mahasiswa) / NIDN (dosen)
    $table->enum('role', ['mahasiswa', 'dosen', 'admin'])
        ->default('mahasiswa')->after('email');
    // Kolom Fortify: two_factor_secret, two_factor_recovery_codes,
    // two_factor_confirmed_at, remember_token, email_verified_at, passkeys
});

// 2. Tabel Schedules (Ruang & Grouping Jadwal)
Schema::create('schedules', function (Blueprint $table) {
    $table->id();
    $table->string('nama_grup_sidang');                    // "Sidang TA Gelombang 1"
    $table->string('ruangan');                             // "Ruang Lab Komputer 3"
    $table->date('tanggal_sidang');
    $table->time('jam_mulai');
    $table->time('jam_selesai');
    $table->timestamps();
});

// 3. Tabel Submissions (Laporan Utama Mahasiswa)
Schema::create('submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('schedule_id')->constrained('schedules');
    $table->string('judul_laporan')->nullable();           // nullable untuk placeholder
    $table->string('file_path')->nullable();               // placeholder sampai mahasiswa upload
    $table->enum('status', ['pending', 'sidang_berjalan', 'revisi', 'selesai'])
        ->default('pending');
    $table->timestamps();
});

// 4. Tabel Revision Notes (Catatan Poin Revisi dari Dosen)
Schema::create('revision_notes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
    $table->foreignId('dosen_id')->constrained('users');
    $table->text('catatan_revisi');
    $table->enum('status_poin', ['open', 'resolved'])->default('open');
    $table->timestamps();
});

// 5. Tabel Revision Attachments (Bukti Revisi dari Mahasiswa)
Schema::create('revision_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('revision_note_id')->constrained('revision_notes')->cascadeOnDelete();
    $table->text('keterangan_mahasiswa')->nullable();
    $table->string('file_path');
    $table->timestamps();
});

// 6. Pivot Dosen ↔ Jadwal
Schema::create('schedule_dosen', function (Blueprint $table) {
    $table->id();
    $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->unique(['schedule_id', 'user_id']);
    $table->timestamps();
});

// 7. Tabel Assistant Conversations (Sesi Chat Asisten Virtual — Admin)
Schema::create('assistant_conversations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
    $table->string('judul')->nullable();                   // ringkasan otomatis topik chat
    $table->timestamps();
});

// 8. Tabel Assistant Messages (Riwayat Percakapan)
Schema::create('assistant_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
    $table->enum('role', ['user', 'assistant']);
    $table->text('content');
    $table->json('tool_calls')->nullable();                 // log query agregat yang dipanggil asisten
    $table->timestamps();
});
```

### FR-06: Penilaian Sidang (Dosen Pembimbing & Dosen Penguji)
*   **Kebutuhan:** Mendokumentasikan hasil penilaian sidang. **Dosen Pembimbing** (dospem) mahasiswa mengisi form sebelum sidang; **Dosen Penguji** (dospeng) mengisi form saat/setelah sidang. Dospem tidak harus ditugaskan (di-plot) ke ruangan sidang mahasiswa bimbingannya — keduanya boleh jadi orang yang sama (mengisi dua form) atau berbeda.
*   **Aturan rule:** Template penilaian ditetapkan admin per **Prodi × Jenis Sidang** (TA/KP/Milestone Design, dll — jenis sidang adalah master dinamis). Template berisi kumpulan item penilaian yang masing-masing memiliki nama dan `maksimal` (range nilai angka). **Skor total** mengikuti rumus:
    ```
    skor_total = Σ(skor_i) / A × B
    ```
    dengan `A` (`nilai_penyebut`) dan `B` (`nilai_pengali`) merupakan input dinamis di template. Tidak ada pass/fail.
*   **Spesifikasi Teknik:**
    *   Tabel baru: `jenis_sidangs`, `pembimbingan` (pivot dospem↔mahasiswa), `assessment_templates` (prodi_id, jenis_sidang_id, nama, nilai_penyebut A, nilai_pengali B, items JSON), `assessment_forms` (submission_id, dosen_id, tipe_penilai∈{dospem,penguji}, template_id, skor_per_item JSON, skor_total float, catatan). Tambah `jenis_sidang_id` ke `schedules`.
    *   Relasi: `submission.user.prodi_id` + `submission.schedule.jenis_sidang_id` → template.
    *   Aktor: admin CRUD template + jenis_sidang + assign dospem; dosen isi form; mahasiswa lihat ringkasan.
    *   Otentikasi/otorisasi: dosen hanya bisa isi jika dospem-nya adalah advisee (via `pembimbingan`) untuk `tipe_penilai=dospem`, atau dosen termasuk `schedule_dosen` submission untuk `tipe_penilai=penguji`. Mahasiswa read-only submission sendiri.
    *   Validasi via Form Request, pesan Bahasa Indonesia.
*   **Spesifikasi UI:** Builder template admin (Alpine repeatable items). Form isian dosen (render dari template items, hitung skor total live). Ringkasan mahasiswa (tabel per-item + skor_total).

### FR-05: Asisten Virtual Analisa Kondisi (Admin)
*   **Kebutuhan:** Admin membutuhkan cara cepat untuk memahami kondisi keseluruhan pelaksanaan sidang tanpa harus membaca tabel satu per satu — misalnya progres mahasiswa, beban/kecepatan review dosen, dan poin revisi yang menumpuk/mandek. Fitur analisa dokumen PDF dengan AI **dihapus** dari cakupan; digantikan asisten chat berbasis LLM yang menjawab pertanyaan admin memakai data agregat sistem (bukan menganalisa isi dokumen laporan).
*   **Spesifikasi Teknik:**
    *   Antarmuka chat di halaman admin (adaptasi halaman *Messages* Metis menjadi satu panel "Asisten Virtual"), aksen kuning pada bubble pesan asisten & tombol kirim.
    *   Admin bertanya bebas dalam Bahasa Indonesia, contoh: *"Dosen mana yang paling lambat resolve revisi bulan ini?"*, *"Berapa mahasiswa yang masih ada poin revisi terbuka lebih dari 7 hari?"*, *"Ringkas kondisi ruang Lab Komputer 3 hari ini."*
    *   LLM: OpenAI-compatible API (configurable `base_url`, `model`, `api_key` via `.env`), dipanggil via **tool calling/function calling** — bukan diberi akses database mentah.
    *   Tool-tool read-only yang disediakan ke LLM (dieksekusi lewat Eloquent query builder di backend, hasil di-*whitelist* kolomnya):
        *   `getStudentProgress(filter)` — status submission & jumlah poin revisi open/resolved per mahasiswa.
        *   `getDosenWorkload(filter)` — jumlah submission ditangani, rata-rata waktu resolve, jumlah poin masih open per dosen.
        *   `getStalledRevisions(thresholdDays)` — daftar poin revisi yang open lebih lama dari batas hari tertentu.
        *   `getScheduleSummary(scheduleId)` — ringkasan kondisi satu ruang/jadwal sidang.
    *   Riwayat percakapan disimpan per admin di tabel `assistant_conversations` & `assistant_messages` (lihat 4.4), termasuk log tool call yang dipanggil untuk keperluan audit.
    *   Respons streaming/synchronous (bukan queue-based) agar terasa seperti chat interaktif; tetap ada timeout & fallback pesan error yang ramah jika LLM tidak merespons.
    *   Guardrail: asisten hanya bisa **membaca** data agregat, tidak pernah diberi tool untuk mengubah/menghapus data (create/update/delete) — mencegah asisten "melakukan aksi" di luar niat admin.
    *   Gate `use-virtual-assistant` — khusus role `admin`.
    *   Error handling: LLM timeout (tampilkan retry), tool call gagal (fallback ke pesan "data tidak tersedia" tanpa mengarang jawaban), rate limit percakapan per admin untuk mencegah penyalahgunaan biaya API.

---

## 5. Catatan Implementasi Frontend
1. Template Metis adalah aset statis Vite (bukan Laravel Blade/Livewire native), sehingga tahap awal development wajib melakukan proses "porting" — bukan copy-paste langsung — terutama untuk komponen yang bergantung pada routing statis antar-file `.html`.
2. Komponen interaktif kompleks (Calendar, Kanban/Order Management) pada template asli dapat diabaikan/dihapus karena di luar cakupan SIMSIDANG; hanya modul Dashboard, Tabel Data, File Manager, Messages, dan Forms yang relevan untuk di-porting.
3. Build produksi tetap memakai `npm run build` terintegrasi via `php artisan serve` + `npm run dev` (Vite HMR) selama development, dan `php artisan optimize` + `npm run build` saat deploy.
