# FRONTEND-GUIDE.md — Porting Template TailAdmin ke Laravel Blade (Tailwind CSS v4)

Template sumber: [TailAdmin](https://github.com/TailAdmin/free-react-tailwind-admin-dashboard) (gratis, open-source, Tailwind CSS). Template ini adalah project Vite **standalone** (HTML/JSX statis per halaman), bukan Blade — dokumen ini adalah checklist supaya porting-nya konsisten dan tidak "copy-paste asal jalan".

> Migrasi Mei 2026: frontend diganti dari Metis (Bootstrap 5.3 + SCSS) → TailAdmin (Tailwind CSS v4). Semua `resources/scss/` sudah dihapus, diganti `resources/css/app.css`. Referensi lama Metis di AGENTS.md/docs sudah usang — ikuti dokumen ini.

## 1. Setup Awal
1. Clone/download template TailAdmin ke folder referensi (gitignored, read-only):
   - `_reference/tailadmin-laravel/` — versi Blade Laravel (TailAdmin/tailadmin-laravel)
   - `_reference/tailadmin-react/` — versi React (TailAdmin/free-react-tailwind-admin-dashboard)
2. Install dependency Tailwind di project Laravel (sudah terpasang):
   - `@tailwindcss/vite`, `tailwindcss`, `heroicons` (heroicons dipakai sebagai SVG inline, bukan dependency JS)
3. Entry point Vite: `resources/css/app.css` (Tailwind) + `resources/js/app.js`:
   ```js
   laravel({
       input: ['resources/css/app.css', 'resources/js/app.js'],
       refresh: true,
   }),
   tailwindcss(),
   ```
   **Tidak ada `tailwind.config.js`** — Tailwind v4 dikonfigurasi via CSS (`@theme`, `@custom-variant`) di `resources/css/app.css`.

## 2. Struktur Styling — `resources/css/app.css`
Satu-satunya file styling. Bagian penting:
- `@import 'tailwindcss'` — entry Tailwind v4.
- `@custom-variant dark (&:is(.dark *));` — dark mode berbasis class `.dark` (di toggle lewat `$store.theme` Alpine di `layouts/app.blade.php`).
- `@theme { ... }` — token warna & shadow. Aksen brand indigo `#6366f1` = `--color-brand-500`. Palet: `brand` (indigo), `gray` (slate), `success`, `warning`, `error`. Token status (`--status-*`) & chart (`--chart-*`) dipakai ApexCharts via `cssVar()`.
- `@utility` kustom: `menu-item`, `menu-item-active`, `badge-status`, `badge-open`, `badge-pending`, `badge-resolved`, `badge-selesai`, `badge-sidang`, `status-pill`, `stats-card`, `stats-icon`, `main-wrapper`, `main-content`, `app-header`.
- **Aturan theming:** hanya token di `@theme` yang boleh diubah bebas. Jangan ganti class di view satu-per-satu untuk theming.

### Badge status
Gunakan komponen `<x-status-badge :status="$submission->status" />` — jangan hardcode warna di Blade. Mapping:
- `pending` → `badge-pending` (kuning)
- `sidang_berjalan` / `revisi` → `badge-open` (kuning)
- `selesai` → `badge-resolved` (hijau)

## 3. Layout
- `resources/views/layouts/app.blade.php` — layout halaman ber-auth (sidebar + header + content). Mendefinisikan Alpine stores `theme` & `sidebar` (toggle expand/collapse, mobile open). Termasuk IIFE anti-flash dark mode di `<head>`.
- `resources/views/layouts/sidebar.blade.php` — sidebar role-based (lihat §4), class `sidebar` dari `@utility`.
- `resources/views/layouts/header.blade.php` — header sticky: toggle sidebar, theme toggle (`$store.theme.toggle()`), notification bell (`notificationBell()` di `resources/js/app.js`), user dropdown + logout.
- `resources/views/layouts/backdrop.blade.php` — overlay mobile saat sidebar terbuka (`x-show="$store.sidebar.isMobileOpen"`).
- `resources/views/layouts/auth.blade.php` — layout halaman login (tanpa sidebar, card centered).

## 4. Role-based Sidebar
Sidebar dinamis berdasarkan `auth()->user()->role`:
```blade
{{-- resources/views/layouts/sidebar.blade.php --}}
@if(auth()->user()->isMahasiswa())
    {{-- menu mahasiswa --}}
@endif
@can('viewDosenMenu')
    {{-- menu dosen --}}
@endcan
@can('viewAdminMenu')
    {{-- menu admin, termasuk Asisten Virtual --}}
@endcan
```
Item aktif pakai `request()->routeIs(...)` + class `menu-item-active`. Ikon = Heroicons SVG inline, class `w-5 h-5`.

## 5. Ikon
- **Bootstrap Icons (`bi bi-*`) sudah dihapus.** Ganti semua ikon lama dengan SVG Heroicons inline (`<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">`).
- Ikon contoh (path standar Heroicons outline):
  - Plus: `M12 4v16m8-8H4`
  - Upload: `M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4`
  - Back arrow: `M10 19l-7-7m0 0l7-7m-7 7h18`
  - Download: `M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4`
  - Check: `M5 13l4 4L19 7`
  - Eye: `M15 12a3 3 0 11-6 0 3 3 0 016 0z` + `M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z`

## 6. Pola Komponen yang Sering Dipakai
- **Kartu:** `rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900`
- **Kartu dengan header:** tambah `<div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">` di atas body.
- **Tabel:** wrapper `overflow-hidden rounded-2xl border ... bg-white` + `overflow-x-auto`; `thead` pakai `bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800`; `tbody` pakai `divide-y divide-gray-100 dark:divide-gray-800`.
- **Button primary:** `inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600`
- **Button outline:** `inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800`
- **Input:** `h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800`
- **Error block (`$errors`):** `rounded-lg border border-error-500/20 bg-error-50 p-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400`
- **Flash success/error** di `app.blade.php` pakai `success-*`/`error-*`.

## 7. ApexCharts
- `resources/js/apex.js` meng-import ApexCharts dan set `window.ApexCharts` — sudah di-import di `resources/js/app.js`.
- Di view dashboard (`admin/dashboard.blade.php`), chart diinisialisasi di `@push('scripts')` dengan `const ApexCharts = window.ApexCharts;` (jangan `import` inline — tidak aman di production).
- Warna chart ambil dari CSS var theme-aware: `cssVar('--chart-pending')`, `--chart-sidang-berjalan`, `--chart-revisi`, `--chart-selesai`, `--chart-open`, `--chart-resolved`.

## 8. Dark Mode
- Toggle: header → `$store.theme.toggle()` (store didefinisikan di `layouts/app.blade.php`, simpan di `localStorage['theme']`).
- Semua warna harus punya varian `dark:` di view. Jangan hardcode warna terang saja.
- ApexCharts: styling dark ditangani `@layer utilities` di `app.css` (`.apexcharts-*`).

## 9. Yang TIDAK Boleh Dilakukan
- Jangan copy-paste class dari `.html`/`.jsx` TailAdmin mentah — selalu terjemahkan ke Blade + variabel Laravel (`route()`, `@auth`, `@foreach`).
- Jangan bawa ikon/dependency JS yang tidak dipakai (Bootstrap, bootstrap-icons, @popperjs/core, sass sudah dihapus — jangan dipasang lagi).
- Jangan hardcode teks Bahasa Inggris bawaan template — ganti ke Bahasa Indonesia sesuai `GLOSSARY.md`.
- Jangan membuat `tailwind.config.js` untuk Tailwind v4 — konfigurasi lewat CSS saja.
