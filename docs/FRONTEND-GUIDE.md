# FRONTEND-GUIDE.md — Porting Template Metis ke Laravel Blade

Template sumber: [Metis — Bootstrap Admin Template](https://github.com/puikinsh/Bootstrap-Admin-Template) (Bootstrap 5.3.8, Alpine.js, Vite, SCSS). Template ini adalah project Vite **standalone** (HTML statis per halaman), bukan Blade — dokumen ini adalah checklist supaya porting-nya konsisten dan tidak "copy-paste asal jalan".

## 1. Setup Awal
1. Clone/download template Metis ke `_reference/metis-template/` (lihat `ARCHITECTURE.md` §5 — folder ini gitignored, read-only, hanya untuk dibaca saat porting).
2. Install `laravel-vite-plugin` di project Laravel (biasanya sudah bawaan Laravel 13 starter kit).
3. Salin isi:
   - `_reference/metis-template/src-modern/styles/scss/*` → `resources/scss/`
   - `_reference/metis-template/src-modern/scripts/*` → `resources/js/`
4. Buat `resources/scss/app.scss` dan `resources/js/app.js` sebagai entry point, daftarkan di `vite.config.js` Laravel:
   ```js
   laravel({
       input: ['resources/scss/app.scss', 'resources/js/app.js'],
       refresh: true,
   }),
   ```

## 2. Konversi Halaman `.html` → Blade
Untuk setiap halaman `src-modern/*.html` yang relevan (lihat daftar prioritas di §4), lakukan:
1. Identifikasi bagian yang **sama di semua halaman**: `<head>`, sidebar nav, topbar, footer → pindahkan ke:
   - `resources/views/layouts/app.blade.php` (halaman dashboard, ada sidebar)
   - `resources/views/layouts/auth.blade.php` (halaman login/register, tanpa sidebar)
2. Bagian unik per halaman → jadi `@yield('content')` / `@section('content')` di view spesifik, mis. `resources/views/dosen/dashboard.blade.php`.
3. Ganti semua path aset statis (`<link href="css/...">`, `<script src="js/...">`) dengan Vite directive Laravel: `@vite(['resources/scss/app.scss', 'resources/js/app.js'])`.
4. Ganti data dummy/hardcoded di HTML asli dengan variabel Blade (`{{ $submission->judul_laporan }}`, `@foreach`, dsb).
5. Alpine.js component (`x-data="searchComponent()"`, dsb) tetap dipakai apa adanya — hanya perlu dipastikan file JS-nya ter-import di `resources/js/app.js` atau di-load sesuai halaman.

## 3. Role-based Sidebar
Sidebar di template asli hard-coded satu set menu untuk semua halaman. Di SIMSIDANG, sidebar **harus** dinamis berdasarkan `auth()->user()->role`:
```blade
{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@can('viewDosenMenu')
    {{-- menu khusus dosen --}}
@endcan
@can('viewAdminMenu')
    {{-- menu khusus admin, termasuk Asisten Virtual --}}
@endcan
```
Definisikan ability ini di `AppServiceProvider::configureGates()` (lihat `AGENTS.md` §2, `PRD-SIMSIDANG-v2.md` FR-01).

## 4. Prioritas Halaman yang Perlu Di-porting
Ambil dari `PRD-SIMSIDANG-v2.md` §5 (Catatan Implementasi Frontend):

| Modul Metis Asli | Dipakai Untuk | Prioritas |
|---|---|---|
| Auth (blank layout) | Login/Register (FR-01) | Tahap 1 |
| Analytics Dashboard | Dashboard dosen (FR-03) | Tahap 1 |
| File Manager | Upload laporan mahasiswa (FR-02) | Tahap 1 |
| Data Tables | Daftar submission, daftar mahasiswa | Tahap 1 |
| Forms | Form input revision notes, form balasan mahasiswa | Tahap 1 |
| Messages | Timeline/chat revisi (FR-04) & panel Asisten Virtual (FR-05) | Tahap 1 (FR-04) / Tahap 3 (FR-05) |
| Reports | Export rekap (Tahap 2) | Tahap 2 |
| Calendar, Kanban/Order Management | **Tidak dipakai** — di luar cakupan SIMSIDANG | — |

Jangan porting modul yang tidak ada di tabel ini kecuali diminta eksplisit oleh user — ini untuk mencegah scope creep dari fitur template yang tidak relevan.

## 5. Theming — Aksen Indigo
Satu-satunya file yang boleh diubah bebas untuk theming: `resources/scss/abstracts/_variables.scss`.

```scss
$primary:   #6366f1;   // Indigo — tombol, active state, sidebar highlight
$secondary: #64748b;
$success:   #10b981;   // status "Resolved"
$warning:   #f59e0b;   // Amber
$info:      #06b6d4;   // Cyan

$font-family-sans-serif: "Inter", system-ui, sans-serif;
$font-size-base: 0.9rem;
$border-radius: 0.75rem;
$box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
```

Aturan tambahan:
- Badge status: `open`/`pending` = kuning (`--status-pending-bg`), `resolved`/`selesai` = hijau (`--status-resolved-bg`). Gunakan `<x-status-badge>` komponen, jangan hardcode warna di Blade.
- Dark mode: gunakan CSS custom properties (`--status-pending-bg`, `--status-resolved-bg`) yang di-override di `_dark.scss`. Jangan hardcode warna badge di view.

## 6. Yang TIDAK Boleh Dilakukan Saat Porting
- Jangan mengubah struktur SCSS bawaan template (folder `abstracts/`, `components/`, `layout/`, dst.) kecuali `_variables.scss` — kalau butuh style baru, tambah file baru di folder yang sesuai, jangan modifikasi file asli template secara langsung (memudahkan tracking drift dari upstream).
- Jangan hardcode teks Bahasa Inggris bawaan template (mis. "Dashboard", "Settings") — ganti ke Bahasa Indonesia sesuai `GLOSSARY.md`.
- Jangan bawa dependency JS yang tidak dipakai (mis. kalau Calendar/Kanban tidak di-porting, jangan ikutkan library JS-nya di `resources/js/`).
