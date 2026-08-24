# ROADMAP.md — SIMSIDANG

Checklist prioritas fitur. Kerjakan Tahap 1 dulu sampai selesai sebelum mulai Tahap 2, kecuali user secara eksplisit minta lain. Centang (`- [x]`) item yang sudah selesai & lulus test. Update file ini setiap kali ada progres — dan catat progres besar di `MEMORY.md` juga.

---

## Tahap 1 — MVP Inti (Wajib Rilis Awal)
Tujuan: satu alur utuh mahasiswa upload → dosen kasih revisi → mahasiswa balas → dosen resolve, bisa jalan end-to-end.

- [x] Scaffold project Laravel 13 + install Fortify
- [x] Migration: `users` (extend), `schedules`, `schedule_dosen`, `submissions`, `revision_notes`, `revision_attachments` (lihat `SCHEMA.md`)
- [x] Porting layout dasar TailAdmin: `layouts/app.blade.php`, `layouts/auth.blade.php`, `sidebar/header/backdrop` (lihat `FRONTEND-GUIDE.md`)
- [x] Override tema warna indigo di `resources/css/app.css` (`@theme` → `brand-500`)
- [x] **FR-01:** Login NIM/NIDN via Fortify, RBAC via Gate (skip 2FA & passkey)
- [x] **FR-02:** Upload submission PDF mahasiswa (validasi 10MB, `Storage::disk('local')`)
- [x] **FR-03:** Dashboard dosen terfilter ruang & tanggal hari ini (eager loading)
- [x] **FR-04 (dosen):** CRUD revision_notes per poin
- [x] **FR-04 (mahasiswa):** Balas teks + upload attachment per poin (validasi format & 5MB)
- [x] **FR-04 (dosen):** Ubah status poin `open` → `resolved`
- [x] Test dasar (Pest/PHPUnit) untuk auth, upload, dan alur revisi
- [x] **Prodi (Program Studi):** master CRUD admin, kolom prodi_id di users, validasi required untuk mahasiswa/dosen

## Tahap 2 — Peningkatan Operasional
Tujuan: memudahkan admin & mengurangi kerja manual, setelah alur inti stabil dipakai.

- [x] **Role admin: CRUD user & jadwal**
- [x] **Import jadwal massal dari Excel**
- [x] **Notifikasi in-app** (Alpine polling) saat ada revisi baru / poin resolved
- [x] **Log riwayat perubahan status submission**
- [x] **Search & filter tabel** (pakai komponen search bawaan TailAdmin)
- [x] **Export rekap status sidang ke PDF/Excel**

## Tahap 3 — Fitur Lanjutan (Nice-to-have)
Tujuan: fitur pembeda, aman ditunda ke rilis berikutnya.

- [x] **FR-05:** Asisten Virtual Admin — migration `assistant_conversations`, `assistant_messages`
- [x] **FR-05:** Tool read-only: `getStudentProgress`, `getDosenWorkload`, `getStalledRevisions`, `getScheduleSummary`
- [x] **FR-05:** UI chat panel (adaptasi halaman Messages TailAdmin)
- [x] **FR-05:** Guardrail read-only + rate limiting percakapan
- [ ] Passkey authentication (Fortify)
- [x] Dark mode toggle
- [x] Dashboard analitik agregat lintas ruang/jadwal (ApexCharts)
- [x] **FR-07:** Baca dengan AI (dosen) — PDF→Markdown (`smalot/pdfparser`) + analisa LLM lokal (reuse infra FR-05), cache 24 jam, tombol "Refresh Analisa", modal di detail submission
- [x] **FR-07:** Test (11) — otorisasi dosen, error 422/500/503/502, cache markdown+respons, refresh paksa, expired >24 jam

## Tahap 4 — Penilaian Sidang (Fitur Baru, luar PRD asli)
Tujuan: dospem + dosen penguji isi form penilaian (rule per prodi × jenis sidang), skor total rumus Σskor / A × B.

### Step 1 — Master data & relasi penilaian
- [x] Migration: `jenis_sidangs`, `pembimbingan` (pivot dospem↔mahasiswa), `add_jenis_sidang_id_to_schedules`
- [x] Model `JenisSidang`, relasi `mahasiswa.hasDosenPembimbing` / `dosen.pembimbingan`
- [x] Factory + Seeder (3 jenis default: TA, KP, Milestone Design; assign jenis ke schedule seed; assign ≥1 dospem per mahasiswa)
- [x] Test: migration seed, relasi dospem–mahasiswa, unicitet pivot

### Step 2 — Template penilaian (admin)
- [x] Migration: `assessment_templates` (prodi_id, jenis_sidang_id, nama, nilai_penyebut A, nilai_pengali B, items JSON), `assessment_forms` (submission_id, dosen_id, tipe_penilai, template_id, skor_per_item, skor_total)
- [x] Model `AssessmentTemplate` + `AssessmentForm` (casts json, accessor `skorTotal`)
- [x] FormRequest (`nilai_penyebut` min:1, `nilai_pengali` min:0, items.*.maksimal integer >0)
- [x] `AssessmentTemplatePolicy` + `AssessmentFormPolicy` (admin full; dosen pakai via relasi) — register di `AppServiceProvider`
- [x] Route admin resource `/admin/jenis-sidangs`, `/admin/assessment-templates`
- [x] Test: skor total rumus, policy admin-only CRUD, validasi A/B & items

### Step 3 — Form isi (dosen) + view (mahasiswa)
- [x] Controller `Dosen/PenilaianController` (index tab Penguji/Pembimbing, create/store/{form}/edit, policy-bound)
- [x] Route dosen `/dosen/penilaian`, `/dosen/submissions/{submission}/penilaian`, `/dosen/penilaian/{form}/edit`
- [x] Controller `Mahasiswa/PenilaianController` (show ringkasan read-only)
- [x] Route mahasiswa `/mahasiswa/submissions/{submission}/penilaian`
- [x] View Blade: builder template admin, form isi dosen (Alpine live-hitung Σskor/A×B), ringkasan mahasiswa
- [x] Test: policy dospem→advisee only, examiner→jadwal only, mahasiswa read-only, satu form per (submission,dosen,tipe), dosen ganda dapat 2 form

### Step 4 — Wire-up & verifikasi penuh
- [x] Sidebar nav (dosen & mahasiswa), notifikasi "ada penilaian baru" untuk mahasiswa, eager-load di daftar
- [x] Seeder lengkap + `migrate:fresh --seed`
- [x] `php artisan test` lulus, `npm run lint`, `vendor/bin/pint`

## Tahap 5 — Deteksi Konflik Jadwal
Tujuan: cegah mahasiswa ter-plot ganda dan dosen double-booked pada jadwal yang waktunya overlap (ruangan tidak dicek).

- [x] Service `ScheduleConflictService` (query overlap setengah-terbuka `[mulai, selesai)`)
- [x] Validasi store/update jadwal: dosen bentrok ditolak; update juga cek ulang anggota ter-plot saat geser tanggal/jam
- [x] Validasi plot mahasiswa (`StoreScheduleMahasiswaRequest`)
- [x] Import CSV: row bentrok → `$failures`, termasuk konflik antar-row dalam satu file
- [x] Blok tampilan `$errors` di view create/edit jadwal admin (belum ada)
- [x] Test Pest (store/update/plot/import + kasus positif)

---

## Catatan
- Setiap kali sebuah item selesai dan lulus test, centang di sini **dan** tambahkan entri di `MEMORY.md` bagian "Log Sesi".
- Kalau ada fitur baru yang muncul di tengah jalan (bukan dari PRD), tambahkan ke tahap yang sesuai di sini dulu sebelum dikerjakan — jangan langsung coding di luar roadmap tanpa dicatat.
