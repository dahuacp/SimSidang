# ROADMAP.md — SIMSIDANG

Checklist prioritas fitur. Kerjakan Tahap 1 dulu sampai selesai sebelum mulai Tahap 2, kecuali user secara eksplisit minta lain. Centang (`- [x]`) item yang sudah selesai & lulus test. Update file ini setiap kali ada progres — dan catat progres besar di `MEMORY.md` juga.

---

## Tahap 1 — MVP Inti (Wajib Rilis Awal)
Tujuan: satu alur utuh mahasiswa upload → dosen kasih revisi → mahasiswa balas → dosen resolve, bisa jalan end-to-end.

- [x] Scaffold project Laravel 13 + install Fortify
- [x] Migration: `users` (extend), `schedules`, `schedule_dosen`, `submissions`, `revision_notes`, `revision_attachments` (lihat `SCHEMA.md`)
- [x] Porting layout dasar Metis: `layouts/app.blade.php`, `layouts/auth.blade.php` (lihat `FRONTEND-GUIDE.md`)
- [x] Override tema warna kuning di `_variables.scss`
- [x] **FR-01:** Login NIM/NIDN via Fortify, RBAC via Gate (skip 2FA & passkey)
- [x] **FR-02:** Upload submission PDF mahasiswa (validasi 10MB, `Storage::disk('local')`)
- [x] **FR-03:** Dashboard dosen terfilter ruang & tanggal hari ini (eager loading)
- [x] **FR-04 (dosen):** CRUD revision_notes per poin
- [x] **FR-04 (mahasiswa):** Balas teks + upload attachment per poin (validasi format & 5MB)
- [x] **FR-04 (dosen):** Ubah status poin `open` → `resolved`
- [x] Test dasar (Pest/PHPUnit) untuk auth, upload, dan alur revisi

## Tahap 2 — Peningkatan Operasional
Tujuan: memudahkan admin & mengurangi kerja manual, setelah alur inti stabil dipakai.

- [x] **Role admin: CRUD user & jadwal**
- [x] **Import jadwal massal dari Excel**
- [x] **Notifikasi in-app** (Alpine polling) saat ada revisi baru / poin resolved
- [x] **Log riwayat perubahan status submission**
- [x] **Search & filter tabel** (pakai komponen search bawaan Metis)
- [x] **Export rekap status sidang ke PDF/Excel**

## Tahap 3 — Fitur Lanjutan (Nice-to-have)
Tujuan: fitur pembeda, aman ditunda ke rilis berikutnya.

- [x] **FR-05:** Asisten Virtual Admin — migration `assistant_conversations`, `assistant_messages`
- [x] **FR-05:** Tool read-only: `getStudentProgress`, `getDosenWorkload`, `getStalledRevisions`, `getScheduleSummary`
- [x] **FR-05:** UI chat panel (adaptasi halaman Messages Metis)
- [x] **FR-05:** Guardrail read-only + rate limiting percakapan
- [ ] Passkey authentication (Fortify)
- [x] Dark mode toggle
- [x] Dashboard analitik agregat lintas ruang/jadwal (ApexCharts)

---

## Catatan
- Setiap kali sebuah item selesai dan lulus test, centang di sini **dan** tambahkan entri di `MEMORY.md` bagian "Log Sesi".
- Kalau ada fitur baru yang muncul di tengah jalan (bukan dari PRD), tambahkan ke tahap yang sesuai di sini dulu sebelum dikerjakan — jangan langsung coding di luar roadmap tanpa dicatat.
