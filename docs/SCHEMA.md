# SCHEMA.md — Skema Database SIMSIDANG (MySQL 8.x)

Referensi cepat skema database, tanpa narasi produk (narasi lengkap ada di `PRD-SIMSIDANG-v2.md`). Gunakan file ini saat menulis migration atau model Eloquent.

## Daftar Tabel
| Tabel | Fungsi |
|---|---|
| `users` | Admin, Dosen, Mahasiswa (satu tabel, dibedakan kolom `role`) |
| `schedules` | Ruang & grouping jadwal sidang |
| `schedule_dosen` | Pivot: dosen mana ditugaskan ke jadwal mana |
| `submissions` | Laporan utama yang diunggah mahasiswa |
| `revision_notes` | Poin revisi dari dosen per submission |
| `revision_attachments` | Balasan/bukti perbaikan dari mahasiswa per poin revisi |
| `assistant_conversations` | Sesi chat Asisten Virtual (admin) |
| `assistant_messages` | Riwayat pesan dalam satu sesi chat asisten |

## ERD (ringkas)
```
users (1) ───< submissions >─── (1) schedules
users (1) ───< revision_notes (sebagai dosen_id)
submissions (1) ───< revision_notes
revision_notes (1) ───< revision_attachments
schedules (M) ──< schedule_dosen >── (M) users (role=dosen)
users (1, role=admin) ───< assistant_conversations
assistant_conversations (1) ───< assistant_messages
```

## Definisi Tabel

### `users` (extend tabel bawaan Fortify)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `username` | string, unique | NIM (mahasiswa) atau NIDN (dosen) |
| `role` | enum(`mahasiswa`,`dosen`,`admin`) | default `mahasiswa` |
| `email`, `password`, dll | bawaan Laravel | |
| `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` | bawaan Fortify | untuk Tahap 3 (passkey), tidak dipakai di MVP inti |

### `schedules`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama_grup_sidang` | string | mis. "Sidang TA Gelombang 1" |
| `ruangan` | string | mis. "Ruang Lab Komputer 3" |
| `tanggal_sidang` | date | |
| `jam_mulai` | time | |
| `jam_selesai` | time | |
| `timestamps` | | |

### `submissions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | FK → `users` | cascade delete |
| `schedule_id` | FK → `schedules` | |
| `judul_laporan` | string, nullable | |
| `file_path` | string, nullable | placeholder sampai mahasiswa upload |
| `status` | enum(`pending`,`sidang_berjalan`,`revisi`,`selesai`) | default `pending` |
| `timestamps` | | |

### `revision_notes`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `submission_id` | FK → `submissions` | cascade delete |
| `dosen_id` | FK → `users` | |
| `catatan_revisi` | text | |
| `status_poin` | enum(`open`,`resolved`) | default `open` |
| `timestamps` | | |

### `revision_attachments`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `revision_note_id` | FK → `revision_notes` | cascade delete |
| `keterangan_mahasiswa` | text, nullable | |
| `file_path` | string | |
| `timestamps` | | |

### `schedule_dosen` (pivot)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `schedule_id` | FK → `schedules` | cascade delete |
| `user_id` | FK → `users` | cascade delete |
| unique | `(schedule_id, user_id)` | |
| `timestamps` | | |

### `assistant_conversations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `admin_id` | FK → `users` | cascade delete |
| `judul` | string, nullable | ringkasan otomatis topik chat |
| `timestamps` | | |

### `assistant_messages`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `conversation_id` | FK → `assistant_conversations` | cascade delete |
| `role` | enum(`user`,`assistant`) | |
| `content` | text | |
| `tool_calls` | json, nullable | log tool/function call yang dipanggil, untuk audit |
| `timestamps` | | |

## Urutan Migration yang Disarankan
1. `users` (extend — tambah `username`, `role`)
2. `schedules`
3. `schedule_dosen`
4. `submissions`
5. `revision_notes`
6. `revision_attachments`
7. `assistant_conversations`
8. `assistant_messages`

## Catatan Query
- Selalu eager load relasi saat menampilkan daftar mahasiswa per dosen: `Submission::with(['user', 'revisionNotes'])`.
- Tool Asisten Virtual (FR-05) read-only. Fitur "bebas query" menambah 2 tool: `queryData` (query builder terstruktur) & `runSqlQuery` (raw SQL SELECT). Keduanya bisa mengakses raw rows SEMUA tabel domain, tapi kolom sensitif (`users.password`, `remember_token`, `two_factor_*`) dan tabel non-domain (cache/sessions/jobs/dll.) diblokir. Guard read-only: validasi SQL (SELECT-only, no multi-statement, no komentar, force LIMIT) + transaksi rollback. Sebelumnya: tool agregat hanya mengembalikan hasil agregasi — kebijakan ini dilonggarkan.
