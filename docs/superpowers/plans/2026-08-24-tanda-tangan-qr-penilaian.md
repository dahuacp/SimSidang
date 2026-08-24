# Tanda Tangan QR Penilaian Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lembar cetak penilaian menampilkan tanda tangan elektronik QR code (nama dosen + tanggal pemberian nilai) menggantikan ruang tanda tangan basah.

**Architecture:** Service baru `QrCodeService` meng-encode teks tanda tangan via bacon/bacon-qr-code `Encoder` lalu merasterisasi matriks ke PNG dengan GD (bacon 3.x tidak punya GdImageBackEnd dan ext-imagick tidak terpasang) → base64 data URI. View cetak memakai `@inject` dan mengganti baris kosong `sig-table` dengan `<img>` QR; tanggal header cetak berpindah dari `Carbon::now()` ke `assessment_forms.created_at`.

**Tech Stack:** Laravel 13, bacon/bacon-qr-code ^3.1 (sudah ter-vendor via Fortify), ext-gd, DomPDF, Pest.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-24-tanda-tangan-qr-penilaian-design.md`
- **JANGAN commit git** — menunggu perintah eksplisit user (aturan lintas sesi dari MEMORY.md).
- Test WAJIB via MySQL: `vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature`. Default sqlite gagal (`pdo_sqlite` tidak terpasang).
- RefreshDatabase MENGHAPUS DB dev `sidangapp2` — backup dulu (`mysqldump -usidang -psidang sidangapp2 > /tmp/opencode/...sql`), restore setelahnya (`php artisan migrate:fresh --seed`).
- Selesai task PHP: `vendor/bin/pint --dirty --format agent`; JS/CSS berubah: `npm run lint`.
- Pesan error/UI Bahasa Indonesia. Nama kelas/route Inggris.
- bacon API terverifikasi (v3.1.1): `Encoder::encode(string $content, ErrorCorrectionLevel $ecLevel, string $encoding = Encoder::DEFAULT_BYTE_MODE_ENCODING, ?Version $forcedVersion = null, bool $prefixEci = true): QrCode`; `ErrorCorrectionLevel::M()` via `__callStatic` (DASPRiD\Enum); `QrCode::getMatrix(): ByteMatrix`; `ByteMatrix::{getWidth,getHeight,get(x,y)}: int`. **Matrix TIDAK termasuk quiet zone** — tambahkan margin 4 modul sendiri saat rasterisasi.

---

### Task 1: Deklarasikan dependensi bacon/bacon-qr-code secara eksplisit

**Files:**
- Modify: `composer.json` (via composer CLI)

- [ ] **Step 1: Require paket**

```bash
composer require bacon/bacon-qr-code:^3.1 --no-interaction
```

Expected: "Using version ^3.1 for bacon/bacon-qr-code" atau "Nothing to install" (sudah ada via Fortify) — yang penting masuk blok `require` composer.json.

- [ ] **Step 2: Verifikasi**

```bash
composer show bacon/bacon-qr-code | head -5 && grep -A8 '"require"' composer.json | grep bacon
```

Expected: versi 3.1.x terpasang dan terdaftar di `composer.json` root.

---

### Task 2: QrCodeService (TDD)

**Files:**
- Create: `app/Services/QrCodeService.php`
- Test: `tests/Feature/QrCodeServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\AssessmentForm` (relasi `dosen`, atribut `created_at`, kolom `dosen.title` nullable pada `users`), factory state `User::factory()->dosen()`.
- Produces: `QrCodeService::signatureText(AssessmentForm $form): string` (teks 3 baris) dan `QrCodeService::penilaianSignature(AssessmentForm $form): string` (`data:image/png;base64,...`) — dipakai Task 3 (view).

- [ ] **Step 1: Tulis test yang gagal**

```php
<?php

use App\Models\AssessmentForm;
use App\Models\User;
use App\Services\QrCodeService;
use Carbon\Carbon;

function scenarioQrForm(): AssessmentForm
{
    $dosen = User::factory()->dosen()->make(['name' => 'Winarti', 'title' => 'S.Kom., M.Kom.']);

    $form = AssessmentForm::make();
    $form->created_at = Carbon::create(2026, 3, 17, 10);
    $form->setRelation('dosen', $dosen);

    return $form;
}

test('teks tanda tangan berisi nama dosen dan tanggal pemberian nilai', function () {
    $service = new QrCodeService;

    expect($service->signatureText(scenarioQrForm()))
        ->toContain('Tanda Tangan Elektronik Penilaian Sidang')
        ->toContain('Dosen: Winarti, S.Kom., M.Kom.')
        ->toContain('Tanggal: 17 Maret 2026');
});

test('tanda tangan penilaian berupa data URI PNG valid', function () {
    $service = new QrCodeService;
    $dataUri = $service->penilaianSignature(scenarioQrForm());

    $prefix = 'data:image/png;base64,';
    expect(str_starts_with($dataUri, $prefix))->toBeTrue();

    $binary = base64_decode(substr($dataUri, strlen($prefix)), true);

    expect($binary)->not->toBeFalse()
        ->and(strlen($binary))->toBeGreaterThan(500)
        ->and(substr($binary, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});
```

Catatan: model dibuat dengan `make()` (tidak disimpan) supaya test murni unit-level; tetap jalan di suite Feature karena `RefreshDatabase` sudah menyediakan DB untuk factory.

- [ ] **Step 2: Jalankan, pastikan gagal**

```bash
vendor/bin/pest tests/Feature/QrCodeServiceTest.php
```

Expected: FAIL — `Class "App\Services\QrCodeService" not found`.

- [ ] **Step 3: Implementasi service**

```php
<?php

namespace App\Services;

use App\Models\AssessmentForm;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class QrCodeService
{
    private const SCALE = 10;

    private const QUIET_ZONE_MODULES = 4;

    public function signatureText(AssessmentForm $form): string
    {
        $dosenName = $form->dosen->name.($form->dosen->title ? ', '.$form->dosen->title : '');
        $tanggal = optional($form->created_at)->locale('id')->translatedFormat('d F Y');

        return "Tanda Tangan Elektronik Penilaian Sidang\n"
            .'Dosen: '.$dosenName."\n"
            .'Tanggal: '.$tanggal;
    }

    public function penilaianSignature(AssessmentForm $form): string
    {
        $matrix = Encoder::encode($this->signatureText($form), ErrorCorrectionLevel::M())->getMatrix();

        $modules = $matrix->getWidth();
        $margin = self::QUIET_ZONE_MODULES * self::SCALE;
        $pixel = ($modules + 2 * self::QUIET_ZONE_MODULES) * self::SCALE;

        $image = imagecreatetruecolor($pixel, $pixel);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $image,
                        $margin + $x * self::SCALE,
                        $margin + $y * self::SCALE,
                        $margin + ($x + 1) * self::SCALE - 1,
                        $margin + ($y + 1) * self::SCALE - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
```

- [ ] **Step 4: Jalankan, pastikan lulus**

```bash
vendor/bin/pest tests/Feature/QrCodeServiceTest.php
```

Expected: PASS (2 test).

---

### Task 3: View cetak — QR menggantikan ruang ttd basah, tanggal dari created_at

**Files:**
- Modify: `resources/views/penilaian/cetak.blade.php` (bari `@php` ~71, blok `sig-table` 186–203)

**Interfaces:**
- Consumes: `App\Services\QrCodeService@penilaianSignature` (Task 2) via `@inject`.
- Produces: tidak ada.

- [ ] **Step 1: Ubah `$tanggal` di blok `@php`**

Ganti:

```blade
$tanggal = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y');
```

menjadi:

```blade
$tanggal = $assessmentForm->created_at->locale('id')->translatedFormat('d F Y');
```

- [ ] **Step 2: Tambah @inject di atas blok `@php`**

```blade
@inject('qrService', 'App\Services\QrCodeService')
```

- [ ] **Step 3: Ganti baris kosong sig-table dengan QR**

Ganti:

```blade
<tr>
    <td style="height: 50px;"></td>
    <td></td>
</tr>
```

menjadi:

```blade
<tr>
    <td style="height: 90px;"></td>
    <td style="text-align: center;">
        <img src="{{ $qrService->penilaianSignature($assessmentForm) }}" alt="QR Tanda Tangan Elektronik" style="width: 80px; height: 80px;">
    </td>
</tr>
```

- [ ] **Step 4: Smoke render via tinker**

```bash
php artisan tinker --execute 'use App\Models\AssessmentForm; $f = AssessmentForm::with("dosen")->first(); echo substr(view("penilaian.cetak", ["assessmentForm" => $f, "dospem" => collect(), "university" => config("university")])->render(), 0, 50);'
```

Expected: keluar `<!DOCTYPE html` tanpa exception. (Kalau DB kosong karena run test sebelumnya, jalankan setelah Task 4 restore.)

---

### Task 4: Update PenilaianCetakTest (TDD view)

**Files:**
- Modify: `tests/Feature/PenilaianCetakTest.php`

**Interfaces:**
- Consumes: helper `scenarioCetak()` existing (baris 12), pola render view existing (baris 124–133).
- Produces: tidak ada.

- [ ] **Step 1: Tulis/update test yang gagal**

Update test `halaman cetak menampilkan data fakultas dan prodi mahasiswa` — tambahkan assertion QR:

```php
    expect($view)
        ->toContain('Fakultas Teknologi Informasi dan Komunikasi')
        ->toContain('Teknik Informatika')
        ->toContain('Sistem Informasi')
        ->toContain('Winarti, S.Kom., M.Kom.')
        ->toContain('Arif Rahman Sudjatmika, S.Kom., M.Kom.')
        ->toContain('EVALUASI PENILAIAN SIDANG')
        ->toContain('data:image/png;base64');
```

Tambahkan test baru di akhir file (jangan lupa `use Carbon\Carbon;` di atas):

```php
test('tanggal cetak dan QR berasal dari tanggal pemberian nilai (form dospem)', function () {
    $s = scenarioCetak('dospem');
    $s['form']->forceFill(['created_at' => Carbon::create(2026, 3, 17, 10)])->save();

    $view = view('penilaian.cetak', [
        'assessmentForm' => $s['form']->load([
            'submission.user.prodi.fakultas',
            'submission.schedule.jenisSidang',
            'dosen',
            'template',
        ]),
        'dospem' => $s['mahasiswa']->dosenPembimbingByUrutan->load('prodi'),
        'university' => config('university'),
    ])->render();

    expect($view)
        ->toContain('Jombang, 17 Maret 2026')
        ->toContain('EVALUASI BIMBINGAN TUGAS AKHIR')
        ->toContain('data:image/png;base64');
});
```

- [ ] **Step 2: Jalankan, pastikan gagal**

```bash
vendor/bin/pest tests/Feature/PenilaianCetakTest.php
```

Expected: FAIL — HTML belum mengandung `data:image/png;base64` dan tanggal masih hari ini (bukan "17 Maret 2026").

- [ ] **Step 3: Terapkan perubahan Task 3 pada view jika belum, lalu jalankan lagi**

```bash
vendor/bin/pest tests/Feature/PenilaianCetakTest.php
```

Expected: PASS semua (7 test existing + 2 test service + 1 baru).

---

### Task 5: Suite penuh + backup/restore DB + lint

- [ ] **Step 1: Backup DB dev**

```bash
mysqldump -usidang -psidang sidangapp2 > /tmp/opencode/sidangapp2_backup_qr.sql
```

- [ ] **Step 2: Jalankan suite Feature penuh**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature
```

Expected: semua lulus KECUALI flaky pre-existing `AssistantToolsTest::getStalledRevisions` (timing ceil — bukan regresi).

- [ ] **Step 3: Restore DB dev**

```bash
php artisan migrate:fresh --seed
```

- [ ] **Step 4: Pint + npm lint**

```bash
vendor/bin/pint --dirty --format agent
npm run lint
```

Expected: bersih keduanya.

---

### Task 6: Dokumentasi

- [ ] **Step 1: GLOSSARY.md** — cek format file, tambahkan entri istilah domain baru jika ada slot yang cocok: "Tanda tangan elektronik (QR)" → kode `QrCodeService` / `penilaianSignature`.
- [ ] **Step 2: MEMORY.md** — update bagian "Status Saat Ini" (fitur terakhir) + entri Log Sesi baru (append-only, terbaru di atas): ringkasan fitur, keputusan (created_at sebagai tanggal nilai; QR ganti ttd basah; GD rasterisasi manual karena bacon 3.x tak punya GdImageBackEnd & imagick tak terpasang), file yang diubah, catatan sesi berikutnya (belum commit).
