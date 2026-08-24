# Desain: Tanda Tangan QR Code pada Lembar Cetak Penilaian

**Tanggal:** 2026-08-24
**Status:** Disetujui (user: "ok lanjut")

## Ringkasan

Lembar cetak hasil penilaian (`penilaian/cetak.blade.php`) kini menyertakan **tanda tangan elektronik berupa QR code** dari dosen penilai (dosen pembimbing maupun dosen penguji). QR menggantikan ruang kosong tanda tangan basah. Isi QR = nama dosen + tanggal pemberian nilai.

## Keputusan

| Aspek | Keputusan |
|---|---|
| Sumber tanggal | `assessment_forms.created_at` (tanpa migration baru). Jika nilai diedit, tanggal tidak berubah. |
| Tanggal header cetak | Ikut berubah dari `Carbon::now()` → `created_at`, agar lembar tercetak konsisten dengan isi QR. |
| Posisi QR | Menempati baris kosong 50px (ruang tanda tangan basah) di blok `sig-table`, di atas nama dosen. Tidak ada lagi slot ttd basah. |
| Isi QR | Plain text 4 baris (lihat bawah), bukan URL verifikasi. |
| Generator QR | `bacon/bacon-qr-code` v3 — sudah ter-vendor via Fortify; ditambahkan eksplisit ke `composer.json`. Output PNG via GD (`ext-gd` tersedia) sebagai base64 data URI untuk DomPDF. |

## Format isi QR

```
Tanda Tangan Elektronik Penilaian Sidang
Dosen: {nama_dosen}{gelar}
Jenis Sidang: {nama_jenis_sidang}
Tanggal: {d F Y} (locale Indonesia)
```

Contoh:

```
Tanda Tangan Elektronik Penilaian Sidang
Dosen: Winarti, S.Kom., M.Kom.
Jenis Sidang: Tugas Akhir
Tanggal: 17 Maret 2026
```

## Komponen

### 1. `app/Services/QrCodeService.php` (baru)

Satu method publik:

```php
public function penilaianSignature(AssessmentForm $form): string
```

- Menyusun teks tanda tangan dari `$form->dosen` (name + title) dan `$form->created_at` (`locale('id')->translatedFormat('d F Y')`).
- Merender QR PNG (ECC Medium, quiet zone default, ukuran internal ~300px) via `ImageRenderer` + `GdImageBackEnd`.
- Return `data:image/png;base64,...`.

View memakai `@inject('qr', 'App\Services\QrCodeService')` agar logika tetap satu titik dan view tetap tipis.

### 2. `resources/views/penilaian/cetak.blade.php`

- `$tanggal = $assessmentForm->created_at->locale('id')->translatedFormat('d F Y')` (menggantikan `Carbon::now()`).
- Baris `<tr><td style="height: 50px;"></td><td></td></tr>` diganti: sel kanan berisi `<img src="{{ $qr->penilaianSignature($assessmentForm) }}" style="width: 80px; height: 80px;">` rata tengah.

### 3. `composer.json`

Tambah `bacon/bacon-qr-code: ^3.1` ke `require` (sudah terunduh sebagai dependensi Fortify — tanpa unduhan baru; melindungi jika Fortify berhenti mewariskannya).

## Yang tidak berubah

- Skema database (tidak ada kolom/migration baru).
- Route & controller cetak (admin/dosen tetap; mahasiswa tetap 403).
- Otorisasi, format lembar lainnya.

## Pengujian

1. **`tests/Feature/QrCodeServiceTest.php` (baru)** — `penilaianSignature()`:
   - Return string berawalan `data:image/png;base64,`.
   - Payload base64 ter-decode dan dimulai magic bytes PNG (`\x89PNG\r\n\x1a\n`) serta non-trivial (>500 byte).
2. **`tests/Feature/PenilaianCetakTest.php` (update)** — render view:
   - HTML mengandung `data:image/png;base64` (QR hadir) — untuk skenario penguji DAN dospem.
   - Dengan `created_at` form yang diset deterministik, HTML menampilkan "Jombang, {tanggal created_at}" (bukan tanggal hari ini).
3. Test controller PDF existing (200 + application/pdf) harus tetap lulus tanpa perubahan.

## Verifikasi

- `vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature` (backup DB dev sebelum run, restore `migrate:fresh --seed` sesudahnya).
- `vendor/bin/pint --dirty --format agent`
- `npm run lint`
