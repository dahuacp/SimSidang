# Plan: Perbaikan Submenu Rekap + UI Assign Dosen Pembimbing

## Tujuan
1. **Submenu "Rekap Export"** → hanya 2 item: "Rekap" dan "Cetak Penilaian" (hapus Export Excel/PDF)
2. **UI Assign Dosen Pembimbing** di halaman **Detail Submission Admin** (`admin/submissions/show`) — karena konteks: mahasiswa sudah punya submission, admin bisa assign dospem I & II langsung di situ.

---

## 1. Submenu Rekap (Sidebar)

### File: `resources/views/layouts/sidebar.blade.php` (lines 133-162)
**Perubahan:**
- Hapus item: "Export Excel" (line 149-152) & "Export PDF" (line 153-156)
- Sisakan: "Rekap" (line 145-148) & "Cetak Penilaian" (line 157-160)
- Update logika active state: `admin.rekap` | `admin.rekap.cetak-penilaian`

### Kompleksitas: Rendah
- Hanya edit template Blade, tidak butuh controller/migration.

---

## 2. Assign Dosen Pembimbing di Detail Submission Admin

### Lokasi: `resources/views/admin/submissions/show.blade.php`
Tambah section baru setelah "Catatan Revisi" / sebelum "Penilaian Sidang".

### UI/UX:
- Section **"Dosen Pembimbing"** dengan 2 slot: **Pembimbing I** dan **Pembimbing II**
- Masing-masing slot pakai **searchableSelect** (komponen Alpine yang sudah dipakai di plotting jadwal) → endpoint `/admin/schedules/search-users?type=dosen`
- Data: pivot `pembimbingan` (`mahasiswa_id`, `dosen_id`, `urutan` 1/2, unique `(mahasiswa_id, urutan)`)
- **Validasi:**
  - Dosen harus `role=dosen` & `prodi_id` sama dengan mahasiswa
  - Tidak boleh duplikat dosen untuk urutan yang sama
  - Tidak boleh dosen yang sama untuk I & II
- Aksi: **1 form** dengan 2 row (I & II), POST ke `/admin/submissions/{submission}/pembimbing`
- **Delete:** Tombol hapus per slot (I/II)

### Backend:
**Route baru:**
```
POST /admin/submissions/{submission}/pembimbing   → Admin\SubmissionController@storePembimbing
DELETE /admin/submissions/{submission}/pembimbing/{dosen}?urutan=1  → Admin\SubmissionController@destroyPembimbing
```

**Controller:** `app/Http/Controllers/Admin/SubmissionController.php`
- `storePembimbing(Request $request, Submission $submission)` 
  - Validasi: `dosen_id` required, `urutan` required in:1,2, dosen role=dosen, prodi_id sama, unique per mahasiswa+urutan
  - Sync/attach ke `pembimbingan`
  - Return redirect with success
- `destroyPembimbing(Submission $submission, User $dosen, Request $request)`
  - Hapus pivot mahasiswa_id + dosen_id + urutan
  - Return redirect with success

**FormRequest:** `app/Http/Requests/StorePembimbingRequest.php`
- `dosen_id.1` required, exists:users,id, role=dosen, prodi_id=mahasiswa.prodi_id
- `dosen_id.2` required, exists:users,id, role=dosen, prodi_id=mahasiswa.prodi_id
- Custom rule: dosen_id.1 != dosen_id.2
- Custom rule: dosen belum jadi pembimbing urutan lain untuk mahasiswa ini

### Frontend (Blade + Alpine):
- Di `admin/submissions/show.blade.php` tambah section dengan:
  - 2 row form (I & II) → tiap row: searchableSelect input + hidden `urutan` + tombol Hapus (kalau sudah terisi)
  - 1 form submit ke `/admin/submissions/{submission}/pembimbing` dengan array `dosen_id[1]`, `dosen_id[2]`
  - Tombol "Hapus" per slot → DELETE ke `/admin/submissions/{submission}/pembimbing/{dosen}?urutan=1` (bisa pakai form method DELETE + tombol submit)

### Testing:
- Unit/Feature test: `tests/Feature/AdminSubmissionPembimbingTest.php`
  - Admin bisa assign dospem I
  - Admin bisa assign dospem II
  - Validasi: dosen harus role=dosen
  - Validasi: dosen.prodi_id === mahasiswa.prodi_id
  - Validasi: dosen tidak duplikat untuk I & II
  - Validasi: urutan 1/2 wajib
  - Delete pembimbing works
  - Non-admin 403

---

## File yang Akan Diubah/Ditambah

### Baru:
- `app/Http/Requests/StorePembimbingRequest.php`
- `tests/Feature/AdminSubmissionPembimbingTest.php`

### Diubah:
- `resources/views/layouts/sidebar.blade.php` — submenu rekap simplify
- `resources/views/admin/submissions/show.blade.php` — tambah section assign pembimbing
- `app/Http/Controllers/Admin/SubmissionController.php` — +`storePembimbing()`, `destroyPembimbing()`
- `routes/web.php` — +2 route baru

---

## Catatan Implementasi
1. **Reuse `searchableSelect`** — endpoint `/admin/schedules/search-users?type=dosen` sudah ada, perlu filter exclude dosen yang sudah jadi pembimbing mahasiswa ini & filter prodi_id.
2. **Form submit** — 1 form dengan 2 row (I & II) array input `dosen_id[1]`, `dosen_id[2]`.
3. **Validation messages** bahasa Indonesia.
4. **Migration** tidak perlu — tabel `pembimbingan` sudah ada dengan struktur lengkap.

---

## Estimasi
- Submenu rekap: ~10 menit
- Assign pembimbing UI + backend + test: ~45-60 menit
- Total: ~1 jam

---

## Keputusan User (Locked)
1. ✅ Endpoint search dosen: pakai existing `/admin/schedules/search-users?type=dosen`
2. ✅ Validasi prodi: wajib cek `dosen.prodi_id === mahasiswa.prodi_id`
3. ✅ Delete: tombol hapus per slot (I/II)
4. ✅ Form: 1 form dengan 2 row (array `dosen_id[1]`, `dosen_id[2]`)

---

## Status
**READY TO IMPLEMENT** — menunggu perintah user untuk mulai eksekusi.