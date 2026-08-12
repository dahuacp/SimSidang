# ARCHITECTURE.md — SIMSIDANG

Dokumen ini menjelaskan struktur folder final dan bagaimana Laravel (backend) dan template TailAdmin (frontend) disatukan dalam satu project. Lihat `PRD-SIMSIDANG-v2.md` bagian 4.1–4.2 untuk konteks keputusan.

> Migrasi Mei 2026: frontend diganti dari Metis (Bootstrap 5.3 + SCSS) → TailAdmin (Tailwind CSS v4). `resources/scss/` sudah dihapus, diganti `resources/css/app.css`. Bagian yang menyebut Metis/SCSS di bawah sudah diperbarui.

## 1. Prinsip Dasar
- Laravel adalah **satu-satunya** aplikasi yang di-deploy. Template TailAdmin **tidak** dijalankan sebagai project Vite terpisah — semua asetnya di-porting masuk ke struktur `resources/` Laravel dan di-build lewat `laravel-vite-plugin`.
- Sumber template TailAdmin (TailAdmin/free-react-tailwind-admin-dashboard dan TailAdmin/tailadmin-laravel) hanya dipakai sebagai **referensi/sumber aset** selama porting, boleh disimpan sementara di folder `_reference/tailadmin-*/` (di-gitignore, tidak ikut ter-deploy) untuk memudahkan copy-paste terkontrol — jangan biarkan folder ini tercampur dengan `resources/`.

## 2. Struktur Folder Final

```
simsidang/
├── app/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Schedule.php
│   │   ├── Submission.php
│   │   ├── RevisionNote.php
│   │   ├── RevisionAttachment.php
│   │   ├── AssistantConversation.php
│   │   └── AssistantMessage.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Mahasiswa/          # controller khusus role mahasiswa
│   │   │   ├── Dosen/              # controller khusus role dosen
│   │   │   └── Admin/              # controller khusus role admin (termasuk Asisten Virtual)
│   │   └── Requests/               # Form Request untuk semua validasi upload/input
│   ├── Policies/                   # Policy per model (Submission, RevisionNote, dst.)
│   ├── Services/
│   │   └── VirtualAssistant/       # Service class untuk tool-calling LLM (FR-05)
│   │       ├── AssistantService.php
│   │       └── Tools/              # getStudentProgress, getDosenWorkload, dst.
│   └── Providers/
│       └── AppServiceProvider.php  # configureGates() untuk RBAC
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php       # hasil porting layout utama TailAdmin (sidebar+header+footer)
│   │   │   ├── sidebar.blade.php   # sidebar role-based
│   │   │   ├── header.blade.php    # header sticky: toggle tema, notifikasi, user menu
│   │   │   ├── backdrop.blade.php  # overlay mobile sidebar
│   │   │   └── auth.blade.php      # hasil porting layout kosong (blank) untuk login
│   │   ├── components/             # Blade component reusable (badge status, riwayat status, dsb)
│   │   ├── mahasiswa/
│   │   ├── dosen/
│   │   └── admin/
│   ├── css/
│   │   └── app.css                 # Tailwind v4 entry + @theme + @utility (porting TailAdmin)
│   └── js/
│       ├── app.js                  # entry point Alpine.js
│       └── components/             # hasil porting components/*.js dari TailAdmin
│
├── database/
│   └── migrations/                 # urutan sesuai SCHEMA.md
│
├── routes/
│   └── web.php                     # route group per role (middleware role:mahasiswa/dosen/admin)
│
├── _reference/
│   └── tailadmin-laravel/          # (gitignored) source asli TailAdmin Blade untuk referensi porting
│   └── tailadmin-react/            # (gitignored) source asli TailAdmin React untuk referensi porting
│
├── AGENTS.md
├── PRD-SIMSIDANG-v2.md
├── ARCHITECTURE.md                 # file ini
├── SCHEMA.md
├── FRONTEND-GUIDE.md
├── ROADMAP.md
├── CODING-STANDARDS.md
├── GLOSSARY.md
├── SETUP.md
└── MEMORY.md
```

## 3. Alur Request (contoh: Dosen memberi catatan revisi)
1. `routes/web.php` — route `POST /dosen/submissions/{submission}/revision-notes`, middleware `auth`, `role:dosen`.
2. `Http/Requests/StoreRevisionNoteRequest.php` — validasi `catatan_revisi` wajib, dsb.
3. `Http/Controllers/Dosen/RevisionNoteController.php` — cek Policy (`RevisionNotePolicy::create`, pastikan dosen ditugaskan ke schedule submission ini via `schedule_dosen`), lalu simpan.
4. Redirect/response ke Blade view yang sudah pakai layout `layouts/app.blade.php` (porting TailAdmin).

## 4. Kenapa Bukan SPA/API Terpisah
Semua rendering pakai Blade + Livewire (untuk bagian yang butuh reaktivitas seperti polling notifikasi dan chat asisten). Tidak ada REST API terpisah untuk frontend, karena template TailAdmin sudah cukup dengan render server-side + Alpine.js untuk interaksi ringan. Ini menyederhanakan auth (session-based, bukan token) dan cocok dengan Fortify.

## 5. Batas Tanggung Jawab Folder (untuk agent)
| Folder | Boleh diubah bebas? | Catatan |
|---|---|---|
| `app/` | Ya | Logic bisnis utama |
| `resources/views/` | Ya | Tapi ikuti struktur layout yang sudah di-porting, jangan bikin layout baru dari nol |
| `resources/css/app.css` | Ya (token theming) | Hanya bagian `@theme` yang boleh diubah bebas untuk warna; struktur utility `@utility` diubah hati-hati & dicatat di `MEMORY.md` |
| `resources/js/components/` | Hati-hati | Hasil porting dari TailAdmin |
| `_reference/tailadmin-*/` | Jangan diubah | Read-only, hanya untuk dibaca/dicopy saat porting |
| `database/migrations/` | Ya, tapi jangan edit migration yang sudah di-`migrate` di lingkungan lain — buat migration baru |
