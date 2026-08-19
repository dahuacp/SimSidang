# SCHEMA.md — Skema Database SIMSIDANG (MySQL 8.x)

Referensi cepat skema database, tanpa narasi produk (narasi lengkap ada di `PRD-SIMSIDANG-v2.md`). Gunakan file ini saat menulis migration atau model Eloquent.

## Daftar Tabel
| Tabel | Fungsi |
|---|---|
| `users` | Admin, Dosen, Mahasiswa (satu tabel, dibedakan kolom `role`). Kolom `prodi_id` FK → `prodis` (nullable — admin tidak punya prodi) |
| `fakultas` | Daftar fakultas (CRUD oleh admin) |
| `prodis` | Daftar program studi (CRUD oleh admin). Kolom `fakultas_id` FK → `fakultas` |
| `schedules` | Ruang & grouping jadwal sidang |
| `schedule_dosen` | Pivot: dosen mana ditugaskan ke jadwal mana |
| `submissions` | Laporan utama yang diunggah mahasiswa |
| `revision_notes` | Poin revisi dari dosen per submission |
| `revision_attachments` | Balasan/bukti perbaikan dari mahasiswa per poin revisi |
| `assistant_conversations` | Sesi chat Asisten Virtual (admin) |
| `assistant_messages` | Riwayat pesan dalam satu sesi chat asisten |

## ERD (ringkas)
```
fakultas (1) ───< prodis (1) ───< users (1) ───< submissions >─── (1) schedules
users (1) ───< revision_notes (sebagai dosen_id)
submissions (1) ───< revision_notes
revision_notes (1) ───< revision_attachments
schedules (M) ──< schedule_dosen >── (M) users (role=dosen)
users (1, role=admin) ───< assistant_conversations
assistant_conversations (1) ───< assistant_messages
```

## Definisi Tabel

### `fakultas`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kode_fakultas` | string, unique | mis. "FTIK", "FEB" |
| `nama_fakultas` | string | mis. "Fakultas Teknologi Informasi dan Komunikasi" |
| `timestamps` | | |

### `prodis`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `kode_prodi` | string, unique | mis. "TI", "SI", "DKV" |
| `nama_prodi` | string | mis. "Teknik Informatika" |
| `fakultas_id` | FK → `fakultas.id`, nullable | wajib di app level untuk semua prodi |
| `timestamps` | | |

### `users` (extend tabel bawaan Fortify)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `username` | string, unique | NIM (mahasiswa) atau NIDN (dosen) |
| `role` | enum(`mahasiswa`,`dosen`,`admin`) | default `mahasiswa` |
| `email`, `password`, dll | bawaan Laravel | |
| `prodi_id` | FK → `prodis.id`, nullable | required untuk role mahasiswa & dosen (divalidasi di app level), nullable untuk admin |
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

### `jenis_sidangs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `nama` | string, unique | "TA", "KP", "Milestone Design" |
| `deskripsi` | string, nullable | |
| `timestamps` | | |

### `pembimbingan` (pivot dosen pembimbing)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `mahasiswa_id` | FK → `users` | |
| `dosen_id` | FK → `users` | |
| `urutan` | tinyint unsigned, default 1 | urutan dosen pembimbing (I=1, II=2) |
| unique | `(mahasiswa_id, urutan)` | maksimal 2 dospem per mahasiswa |
| `timestamps` | | |

### `assessment_templates`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `prodi_id` | FK → `prodis` | |
| `jenis_sidang_id` | FK → `jenis_sidangs` | |
| `nama` | string | |
| `nilai_penyebut` | int, default 1 | penyebut A pada rumus `Σskor / A × B` |
| `nilai_pengali` | int, default 100 | pengali B |
| `items` | json | array item: `{name, maksimal, urutan}` |
| unique | `(prodi_id, jenis_sidang_id)` | satu template per kombinasi |

### `assessment_forms`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `submission_id` | FK → `submissions` | |
| `dosen_id` | FK → `users` | |
| `tipe_penilai` | enum(`dospem`,`penguji`) | |
| `template_id` | FK → `assessment_templates` | |
| `skor_per_item` | json | `{item: idx, skor: value}` |
| `skor_total` | float, nullable | auto-hitung `Σskor / A × B` |
| `catatan` | text, nullable | |
| unique | `(submission_id, dosen_id, tipe_penilai)` | |

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
2. `fakultas`
3. `prodis`
4. `add_fakultas_id_to_prodis` (foreign key → `fakultas`)
5. `add_prodi_id_to_users` (foreign key → `prodis`)
6. `schedules`
7. `schedule_dosen`
8. `submissions`
9. `revision_notes`
10. `revision_attachments`
11. `jenis_sidangs`
12. `pembimbingan`
13. `add_jenis_sidang_id_to_schedules`
14. `add_urutan_to_pembimbingan`
15. `assessment_templates`
16. `assessment_forms`
17. `assistant_conversations`
18. `assistant_messages`

## Catatan Query
- Selalu eager load relasi saat menampilkan daftar mahasiswa per dosen: `Submission::with(['user', 'revisionNotes'])`.
- Tool Asisten Virtual (FR-05) read-only. Fitur "bebas query" menambah 2 tool: `queryData` (query builder terstruktur) & `runSqlQuery` (raw SQL SELECT). Keduanya bisa mengakses raw rows SEMUA tabel domain, tapi kolom sensitif (`users.password`, `remember_token`, `two_factor_*`) dan tabel non-domain (cache/sessions/jobs/dll.) diblokir. Guard read-only: validasi SQL (SELECT-only, no multi-statement, no komentar, force LIMIT) + transaksi rollback. Sebelumnya: tool agregat hanya mengembalikan hasil agregasi — kebijakan ini dilonggarkan.
