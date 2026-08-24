# Desain: Deteksi Konflik Jadwal (Mahasiswa + Dosen)

Tanggal: 2026-08-24
Status: Disetujui user (brainstorming sesi 2026-08-24)

## Latar Belakang

Admin bisa membuat jadwal sidang dan plot mahasiswa/dosen tanpa pemeriksaan bentrok
apa pun (`StoreScheduleRequest` hanya memvalidasi format). Akibatnya satu mahasiswa
bisa ter-plot ke dua sidang yang berlangsung bersamaan, dan satu dosen bisa kebagian
dua ruang pada jam yang sama. Fitur ini menutup celah tersebut.

## Keputusan (hasil brainstorming)

| Pertanyaan | Keputusan |
|---|---|
| Jenis konflik yang dicek | Mahasiswa ganda + Dosen double-booked |
| Konflik ruang | **Tidak** dicek; master Ruang **dibatalkan**; kolom `ruangan` tetap string bebas |
| Perilaku saat bentrok | Blokir total (validasi gagal), tanpa jalur bypass/paksa |
| Import CSV | Ikut dicek — row bentrok masuk `$failures` (pola "Import sebagian gagal") |

## Aturan Konflik

Dua jadwal A dan B **bentrok** jika dan hanya jika:

```
A.tanggal_sidang == B.tanggal_sidang
AND A.jam_mulai < B.jam_selesai
AND A.jam_selesai > B.jam_mulai
```

Interval setengah-terbuka `[jam_mulai, jam_selesai)` — sidang `09:00–11:00` dan
`11:00–13:00` **legal** (nyambung langsung), sedangkan `09:00–11:00` dan
`10:30–12:00` bentrok.

## Komponen

### 1. Service baru: `app/Services/ScheduleConflictService.php`

Satu-satunya tempat logika deteksi (dipakai FormRequest + Import, dites langsung).

```php
public function findDosenConflicts(array $dosenIds, string $tanggal, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection;
public function findMahasiswaConflicts(array $userIds, string $tanggal, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection;
```

- Query inti: `Schedule::whereDate('tanggal_sidang', $tanggal)`
  `->where('jam_mulai', '<', $jamSelesai)->where('jam_selesai', '>', $jamMulai)`
  `->when($ignoreScheduleId, fn ($q) => $q->whereKeyNot($ignoreScheduleId))`
  `->whereHas('dosens'|'mahasiswas', fn ($q) => $q->whereIn('users.id', $userIds))`
  dengan eager-load relasi terkait untuk menyusun pesan.
- Return: collection terstruktur `[user => Collection<Schedule>]` agar pesan error bisa
  menyebut nama orang + jadwal bentrok.
- Array `userIds` kosong → return kosong (tidak query).

### 2. Form Request jadwal: `StoreScheduleRequest` & `UpdateScheduleRequest`

Hook `withValidator`:

- Jika `dosens[]` terisi → cek `findDosenConflicts` terhadap jadwal lain
  (Update meng-ignore dirinya sendiri via route parameter).
- **Update juga cek ulang seluruh anggota jadwal**: dosen dari input DAN mahasiswa
  yang sudah ter-plot di pivot `schedule_mahasiswa` — karena menggeser tanggal/jam
  jadwal bisa membuat anggota lama bentrok dengan jadwal lain.
- Error ditambahkan ke key `dosens` (dan `mahasiswas` untuk kasus geser-jam):
  `"Budi (0011020001) sudah ada di jadwal 'Sidang TA Gelombang 1' (15/08/2026 09:00–11:00)."`

### 3. Form Request plotting: `StoreScheduleMahasiswaRequest`

Hook `withValidator`: setelah rule dasar lulus, cek `findMahasiswaConflicts`
(`[user_id]`, data waktu dari `$this->route('schedule')`). Error ke key `user_id`.

### 4. Import: `app/Imports/ScheduleImport.php`

Di dalam loop `collection()` (sebelum `Schedule::create`):

- Kumpulkan kandidat `[tanggal, jamMulai, jamSelesai, dosenIds]` dari row.
- Cek terhadap DB via service (tanpa ignore).
- Cek terhadap **row lain dalam file yang sama** yang sudah diterima di batch ini —
  bandingkan in-memory terhadap daftar jadwal yang baru saja dibuat import.
- Bentrok → throw `\Exception` → tertangkap try/catch existing → masuk
  `$this->failures[] = 'Baris N: ...'`; row lain tetap diproses.

### 5. View: blok error validasi (BARU)

`resources/views/admin/schedules/create.blade.php` dan `edit.blade.php` saat ini
**belum menampilkan `$errors` sama sekali** — validasi gagal akan diam saja. Tambahkan
blok alert merah (pola alert yang sudah ada di layout) yang me-render semua
`$errors->all()`. Halaman edit adalah target redirect-back `storeMahasiswa`, jadi
error plot mahasiswa juga tampil di sana.

### 6. Skema database

**Tidak ada migration baru.** Semua data yang dibutuhkan deteksi sudah ada
(`schedules.tanggal_sidang/jam_mulai/jam_selesai`, pivot `schedule_dosen`,
`schedule_mahasiswa`).

## Testing (Pest, feature)

Scenario helper baru di test jadwal existing. Kasus wajib:

1. Store jadwal dengan dosen bentrok → redirect back, session errors menyebut nama grup bentrok.
2. Store dengan dosen sama tapi tanggal beda → sukses.
3. Jam nempel (`09:00–11:00` vs `11:00–13:00`) → sukses (bukan konflik).
4. Update jadwal meng-ignore dirinya sendiri (ubah ruangan saja) → sukses.
5. Update menggeser jam sehingga dosen ter-plotnya bentrok jadwal lain → ditolak.
6. Update menggeser jam sehingga mahasiswa ter-plotnya bentrok jadwal lain → ditolak.
7. Plot mahasiswa ke jadwal overlap → ditolak, pesan menyebut jadwal bentrok.
8. Plot mahasiswa ke jadwal beda tanggal/jam nempel → sukses.
9. Import: row bentrok dengan DB → masuk failures, row lain tetap masuk.
10. Import: dua row dalam satu file saling bentrok → salah satu masuk failures.
11. Unit/service: `findDosenConflicts`/`findMahasiswaConflicts` return pasangan benar (positif & negatif).

Verifikasi wajib sesuai AGENTS.md: suite MySQL (`vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature`),
backup DB sebelum + restore sesudah, `npm run lint`, `vendor/bin/pint --dirty`.

## Edge Cases

- `dosens[]` kosong / tidak dikirim → lewati cek dosen.
- Interval nempel tidak bentrok (setengah-terbuka) — didokumentasikan + dites.
- Update harus exclude self supaya tidak false-positive dengan datanya sendiri.
- Import intra-batch: jadwal yang baru dibuat di baris lebih awal ikut jadi pembanding.
- Format jam dari CSV dinormalisasi `H:i` oleh parser existing sebelum dicek.

## Di Luar Scope (YAGNI)

- Deteksi konflik ruangan & master data Ruang.
- Warning + override paksa.
- Notifikasi ke dosen/mahasiswa saat jadwal yang memuat mereka diubah.
