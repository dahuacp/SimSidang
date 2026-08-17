# GLOSSARY.md — Pemetaan Istilah Domain SIMSIDANG

Domain project ini berbahasa Indonesia (akademik), tapi konvensi Laravel biasanya bahasa Inggris untuk nama class/route, dan bahasa Indonesia untuk kolom database & label UI (sesuai keputusan di PRD). File ini jadi rujukan tunggal supaya penamaan konsisten di seluruh codebase — jangan menerjemahkan istilah secara ad-hoc di tempat lain.

| Istilah Domain (Indonesia) | Arti | Nama Tabel/Kolom | Nama Model/Class | Catatan |
|---|---|---|---|---|
| Prodi / Program Studi | Study program (major) | `prodis` table (`kode_prodi`, `nama_prodi`)<br>`users.prodi_id` (FK) | `Prodi` | Mahasiswa & dosen wajib punya prodi; admin tidak perlu |
| Mahasiswa | User dengan role peserta sidang | `users.role = 'mahasiswa'` | `User` (bukan model terpisah) | Login pakai NIM |
| Dosen Penguji | User dengan role penguji | `users.role = 'dosen'` | `User` | Login pakai NIDN |
| Admin | User pengelola sistem | `users.role = 'admin'` | `User` | Akses Asisten Virtual |
| NIM | Nomor Induk Mahasiswa | `users.username` | — | Unique, dipakai sebagai login mahasiswa |
| NIDN | Nomor Induk Dosen Nasional | `users.username` | — | Unique, dipakai sebagai login dosen |
| Ruang Sidang | Lokasi fisik pelaksanaan sidang | `schedules.ruangan` | `Schedule` | |
| Grouping Jadwal | Pengelompokan sesi sidang | `schedules.nama_grup_sidang` | `Schedule` | mis. "Sidang TA Gelombang 1" |
| Laporan / Berkas Laporan | Dokumen PDF yang diunggah mahasiswa | `submissions.file_path`, `submissions.judul_laporan` | `Submission` | |
| Poin Revisi | Satu item catatan perbaikan dari dosen | `revision_notes` | `RevisionNote` | Granular, per item bukan satu blok teks |
| Catatan Revisi | Isi teks dari satu poin revisi | `revision_notes.catatan_revisi` | — | |
| Status Poin (Open/Resolved) | Status penyelesaian satu poin revisi | `revision_notes.status_poin` | — | enum: `open`, `resolved` |
| Bukti Perbaikan / Lampiran | File/penjelasan balasan mahasiswa atas satu poin revisi | `revision_attachments` | `RevisionAttachment` | |
| Keterangan Mahasiswa | Penjelasan teks yang menyertai bukti perbaikan | `revision_attachments.keterangan_mahasiswa` | — | |
| Sidang | Sesi ujian/presentasi TA/KP | — | — | Konsep event, direpresentasikan lewat `Schedule` + `Submission.status` |
| Asisten Virtual | Fitur chat AI read-only untuk admin (FR-05) | `assistant_conversations`, `assistant_messages` | `AssistantConversation`, `AssistantMessage` | Bukan menganalisa dokumen — hanya data agregat |
| Baca dengan AI | Fitur dosen menganalisa laporan submission via LLM (FR-07) | cache di `ai-cache/{submission_id}.md` & `{submission_id}_response.json` | `AiSubmissionService` (controller `Dosen/AiSubmissionController`) | PDF→Markdown (`smalot/pdfparser`) + LLM lokal; output ringkasan + rekomendasi revisi Bab 1,3,4,5; cache 24 jam |
| Analisa / Analisis | Hasil LLM atas isi dokumen laporan | — | — | Beda dari "Asisten Virtual": ini menganalisa isi dokumen, bukan data agregat |
| Jenis Sidang | Tipe sesi sidang, master dinamis (TA/KP/Milestone Design) | `jenis_sidangs` (`nama`, `deskripsi`), FK `schedules.jenis_sidang_id` | `JenisSidang` | Satu jenis per jadwal sidang |
| Dosen Pembimbing (dospem) | Dosen pembimbing/akademik mahasiswa | pivot `pembimbingan` (`mahasiswa_id`, `dosen_id`) | (pivot, lewat relasi User) | Boleh beda dengan dosen penguji; boleh lebih dari satu per mahasiswa |
| Dosen Penguji (dospeng) | Dosen yang ditugaskan ke sebuah jadwal/route sidang | `schedule_dosen` | (relasi `User.schedulesAsDosen`) | Penilai saat sidang; dapat juga jadi dospem |
| Template Penilaian | Aturan item penilaian (per prodi × jenis sidang) | `assessment_templates` (`prodi_id`, `jenis_sidang_id`, `nama`, `nilai_penyebut` A, `nilai_pengali` B, `items` JSON) | `AssessmentTemplate` | Admin kelola; item punya `nama`, `maksimal`, `urutan` |
| Form Penilaian | Isian penilaian yang diisi seorang dosen | `assessment_forms` (`submission_id`, `dosen_id`, `tipe_penilai`∈{dospem,penguji}, `template_id`, `skor_per_item` JSON, `skor_total`) | `AssessmentForm` | Skor total = Σskor / A × B; tidak ada pass/fail |
| Nilai Penyebut (A) | Pembagi normalisasi skor total | `assessment_templates.nilai_penyebut` | — | integer, `min:1` |
| Nilai Pengali (B) | Pengali/akar skala output skor total | `assessment_templates.nilai_pengali` | — | integer, `min:0` |

## Status Submission (`submissions.status`)
| Nilai enum | Arti Indonesia |
|---|---|
| `pending` | Belum sidang / belum ada aktivitas |
| `sidang_berjalan` | Sedang berlangsung sidang |
| `revisi` | Sidang selesai, masih ada poin revisi terbuka |
| `selesai` | Semua poin revisi resolved |

## Aturan Umum
- Kolom database & enum value: **Bahasa Indonesia**, `snake_case` (sesuai keputusan di PRD/SCHEMA).
- Nama class PHP, method, route: **Bahasa Inggris**, mengikuti konvensi Laravel standar.
- Label di UI (Blade): **Bahasa Indonesia**, konsisten dengan istilah di tabel atas — jangan campur istilah baru yang tidak ada di sini tanpa menambahkannya ke glossary ini dulu.
- Kalau menemukan istilah domain baru yang belum ada di sini saat development, **tambahkan ke tabel ini** sebelum dipakai di kode, supaya tidak terjadi inkonsistensi (mis. sebagian kode pakai "catatan_revisi", sebagian lain "revision_note_text").
