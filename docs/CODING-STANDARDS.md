# CODING-STANDARDS.md — SIMSIDANG

Konvensi kode untuk project ini. Ikuti ini secara default kecuali ada instruksi lain dari user.

## 1. Gaya Kode
- **PSR-12** untuk PHP. Jalankan `./vendor/bin/pint` (Laravel Pint) sebelum commit.
- **Strict typing** di file baru: `declare(strict_types=1);` di awal file class.
- Method Eloquent model pendek & deskriptif; logic bisnis kompleks masuk ke `app/Services/`, bukan menumpuk di Controller.

## 2. Penamaan
| Jenis | Konvensi | Contoh |
|---|---|---|
| Migration | `snake_case`, deskriptif, urut waktu | `2026_01_01_000001_create_schedules_table.php` |
| Model | `PascalCase`, singular | `RevisionNote`, bukan `RevisionNotes` |
| Controller | `PascalCase` + `Controller`, dikelompokkan per role folder | `Dosen/RevisionNoteController` |
| Form Request | `PascalCase` + `Request`, verb di depan | `StoreRevisionNoteRequest`, `UpdateSubmissionRequest` |
| Policy | `PascalCase` + `Policy` | `SubmissionPolicy` |
| Route name | `dot.case`, prefix role | `dosen.submissions.index` |
| Blade view | `kebab-case` folder per role | `resources/views/dosen/submissions/index.blade.php` |
| Kolom database | `snake_case` Bahasa Indonesia sesuai domain (lihat `GLOSSARY.md`) | `catatan_revisi`, `status_poin` |

## 3. Validasi & Form Request
- **Semua** input dari form (terutama upload file) wajib lewat Form Request Laravel, tidak boleh validasi inline di Controller.
- Aturan file upload eksplisit sesuai PRD:
  - Submission mahasiswa: `.pdf`, max 10MB.
  - Attachment revisi: `.pdf`, `.docx`, `.jpeg`, `.png`, max 5MB per file.
- Pesan error validasi dalam Bahasa Indonesia.

## 4. Otorisasi
- RBAC lewat Laravel **Gate**, didefinisikan di `AppServiceProvider::configureGates()` (bukan tersebar di banyak file).
- Setiap Controller action yang menyentuh data milik user lain (mis. dosen buka submission mahasiswa) **wajib** cek Policy — jangan andalkan filter query saja tanpa authorization check eksplisit (`$this->authorize(...)`).
- Untuk Asisten Virtual (FR-05): gate `use-virtual-assistant` khusus `role === 'admin'`, dicek di setiap request, bukan hanya di middleware routing.

## 5. Testing
- Framework: **Pest** (lebih ringkas dari PHPUnit klasik, tapi PHPUnit tetap boleh kalau lebih cocok untuk kasus tertentu).
- Setiap FR baru minimal punya:
  - 1 feature test untuk "happy path" (alur normal berhasil)
  - 1 feature test untuk otorisasi gagal (role salah tidak bisa akses)
  - 1 test untuk validasi gagal (input tidak valid ditolak)
- File upload di test pakai `Storage::fake('local')`, jangan upload file sungguhan ke disk.
- Test untuk tool Asisten Virtual (FR-05) harus mock response LLM — jangan panggil API LLM sungguhan di test suite.

## 6. Commit Message
Format: `<tipe>(<scope>): <ringkasan singkat>`
Contoh:
- `feat(revision): tambah endpoint resolve poin revisi`
- `fix(upload): validasi ukuran file attachment`
- `docs(memory): update log sesi porting dashboard dosen`

Tipe yang dipakai: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`.

## 7. Sebelum Menandai Task Selesai
1. `php artisan test` — semua test hijau.
2. `./vendor/bin/pint` — format code.
3. `npm run lint` — untuk perubahan di `resources/js`.
4. Update `MEMORY.md` (lihat protokol wajib di `AGENTS.md` §6).
5. Kalau task menyelesaikan item di `ROADMAP.md`, centang item tersebut.
