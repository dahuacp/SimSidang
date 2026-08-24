# Deteksi Konflik Jadwal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Blokir penyimpanan jadwal, plotting mahasiswa, dan import CSV jika ada dosen/mahasiswa yang berada di dua jadwal dengan waktu overlap pada tanggal sama.

**Architecture:** Service tunggal `ScheduleConflictService` (query overlap setengah-terbuka `[mulai, selesai)`) dipakai oleh 3 FormRequest + `ScheduleImport`. Tanpa migration baru. View dapat blok ringkasan error (belum ada).

**Tech Stack:** Laravel 13 / PHP 8.4, Eloquent, Pest (feature tests), Blade + Tailwind v4.

## Global Constraints

- **DILARANG commit git sama sekali** (instruksi user sesi ini).
- Pesan error/validasi **Bahasa Indonesia**.
- Semua input lewat FormRequest (aturan proyek); logika deteksi hanya di `ScheduleConflictService` (DRY).
- Konflik = `tanggal_sidang` sama DAN `A.jam_mulai < B.jam_selesai AND A.jam_selesai > B.jam_mulai` (setengah-terbuka; jam nempel legal).
- `ruangan` TIDAK dicek; tanpa master Ruang; tanpa migration.
- Jalankan Pest SELALU dengan `vendor/bin/pest --configuration phpunit.mysql.xml ...`; `RefreshDatabase` MENGHAPUS DB dev `.env` (`sidangapp2`) → backup `mysqldump` sebelum run pertama, restore `php artisan migrate:fresh --seed` setelah run terakhir.
- Sebelum selesai: `vendor/bin/pint --dirty --format agent`, `npm run lint`.
- Glosarium: jadwal=schedule, plot mahasiswa=plotting, dosen pembimbing/penguji tak berubah.

---

### Task 1: `ScheduleConflictService` + unit test

**Files:**
- Create: `app/Services/ScheduleConflictService.php`
- Create: `tests/Feature/ScheduleConflictServiceTest.php`

**Interfaces:**
- Produces (dipakai Task 2–4):
  - `App\Services\ScheduleConflictService::findDosenConflicts(array $dosenIds, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Illuminate\Support\Collection` — key = user id, value = `array{user: User, schedules: Collection<int, Schedule>}`.
  - `::findMahasiswaConflicts(array $userIds, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection` — bentuk sama.
  - `::describeConflict(array{user: User, schedules: Collection} $entry): array` — daftar string pesan Indonesia.

- [ ] **Step 1: Tulis test yang gagal**

Buat `tests/Feature/ScheduleConflictServiceTest.php`:

```php
<?php

use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleConflictService;

test('menemukan konflik dosen pada rentang waktu overlap', function () {
    $dosen = User::factory()->dosen()->create();

    $pagi = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Pagi',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $pagi->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-20', '10:30', '12:00');

    expect($hasil)->toHaveCount(1)
        ->and($hasil[$dosen->id]['user']->id)->toBe($dosen->id)
        ->and($hasil[$dosen->id]['schedules']->pluck('id')->all())->toContain($pagi->id);
});

test('jam menyambung langsung tidak dianggap konflik', function () {
    $dosen = User::factory()->dosen()->create();

    $pagi = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $pagi->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-20', '11:00', '13:00');

    expect($hasil)->toHaveCount(0);
});

test('tanggal berbeda tidak dianggap konflik', function () {
    $dosen = User::factory()->dosen()->create();

    $jadwal = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $jadwal->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-21', '09:00', '11:00');

    expect($hasil)->toHaveCount(0);
});

test('ignoreScheduleId mengecualikan jadwal itu sendiri', function () {
    $dosen = User::factory()->dosen()->create();

    $jadwal = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $jadwal->dosens()->attach($dosen->id);

    $hasil = app(ScheduleConflictService::class)
        ->findDosenConflicts([$dosen->id], '2026-08-20', '09:00', '11:00', $jadwal->id);

    expect($hasil)->toHaveCount(0);
});

test('menemukan konflik mahasiswa', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $jadwal = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $jadwal->mahasiswas()->attach($mahasiswa->id);

    $hasil = app(ScheduleConflictService::class)
        ->findMahasiswaConflicts([$mahasiswa->id], '2026-08-20', '10:00', '12:00');

    expect($hasil)->toHaveCount(1)
        ->and($hasil[$mahasiswa->id]['schedules'])->not->toBeEmpty();
});

test('daftar userId kosong mengembalikan koleksi kosong', function () {
    $hasil = app(ScheduleConflictService::class)->findDosenConflicts([], '2026-08-20', '09:00', '11:00');

    expect($hasil)->toHaveCount(0);
});
```

- [ ] **Step 2: Backup DB lalu jalankan test, pastikan gagal**

```bash
mysqldump -usidang -psidang sidangapp2 > /tmp/opencode/sidangapp2_backup.sql
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/ScheduleConflictServiceTest.php
```
Expected: ERROR/FAIL — `Class "App\Services\ScheduleConflictService" not found`. (Backup cukup sekali; run berikutnya tidak perlu backup ulang.)

- [ ] **Step 3: Tulis implementasi minimal**

Buat `app/Services/ScheduleConflictService.php`:

```php
<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    public function findDosenConflicts(array $dosenIds, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection
    {
        return $this->findConflicts($dosenIds, 'dosens', $tanggalSidang, $jamMulai, $jamSelesai, $ignoreScheduleId);
    }

    public function findMahasiswaConflicts(array $userIds, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection
    {
        return $this->findConflicts($userIds, 'mahasiswas', $tanggalSidang, $jamMulai, $jamSelesai, $ignoreScheduleId);
    }

    /**
     * @param  array<int, array{user: User, schedules: Collection<int, Schedule>}>|Collection  $entry
     * @return list<string>
     */
    public function describeConflict($entry): array
    {
        return collect($entry['schedules'])
            ->map(fn (Schedule $schedule): string => sprintf(
                '%s (%s) sudah ada di jadwal "%s" pada %s pukul %s-%s.',
                $entry['user']->name,
                $entry['user']->username,
                $schedule->nama_grup_sidang,
                $schedule->tanggal_sidang->translatedFormat('d/m/Y'),
                $schedule->jam_mulai->format('H:i'),
                $schedule->jam_selesai->format('H:i'),
            ))
            ->all();
    }

    private function findConflicts(array $userIds, string $relation, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        $schedules = Schedule::query()
            ->whereDate('tanggal_sidang', $tanggalSidang)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->when($ignoreScheduleId !== null, fn ($query) => $query->whereKeyNot($ignoreScheduleId))
            ->whereHas($relation, fn ($query) => $query->whereIn('users.id', $userIds))
            ->with([$relation => fn ($query) => $query->whereIn('users.id', $userIds)])
            ->get();

        $result = collect();

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $conflicting = $schedules
                ->filter(fn (Schedule $schedule): bool => $schedule->{$relation}->contains('id', $user->id))
                ->values();

            if ($conflicting->isNotEmpty()) {
                $result->put($user->id, [
                    'user' => $user,
                    'schedules' => $conflicting,
                ]);
            }
        }

        return $result;
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/ScheduleConflictServiceTest.php
```
Expected: PASS 6 test.

---

### Task 2: Validasi store/update jadwal (FormRequest)

**Files:**
- Modify: `app/Http/Requests/StoreScheduleRequest.php`
- Modify: `app/Http/Requests/UpdateScheduleRequest.php`
- Create: `tests/Feature/AdminScheduleConflictTest.php`

**Interfaces:**
- Consumes: `ScheduleConflictService` (Task 1).
- Produces: error key `dosens` (store & update) dan `mahasiswas` (update saja) di validator; pesan memuat nama grup jadwal bentrok.

- [ ] **Step 1: Tulis test yang gagal**

Buat `tests/Feature/AdminScheduleConflictTest.php`:

```php
<?php

use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\User;

test('store jadwal dengan dosen bentrok ditolak', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $existing = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Pagi',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $existing->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Grup Baru',
        'ruangan' => 'Ruang X',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ]);

    $response->assertSessionHasErrors('dosens');
    expect(session('errors')->first('dosens'))->toContain('Grup Pagi');
    $this->assertDatabaseCount('schedules', 1);
});

test('store jadwal dengan dosen sama tapi tanggal berbeda diterima', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $existing = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $existing->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $this->actingAs($admin)->post(route('admin.schedules.store'), [
        'nama_grup_sidang' => 'Grup Baru',
        'ruangan' => 'Ruang X',
        'tanggal_sidang' => '2026-08-21',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ])->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseCount('schedules', 2);
});

test('update menggeser jam hingga dosen bentrok jadwal lain ditolak', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
    ]);
    $target->dosens()->attach($dosen->id);
    $lain = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Lain',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:30',
        'jam_selesai' => '12:30',
    ]);
    $lain->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.schedules.update', $target), [
        'nama_grup_sidang' => $target->nama_grup_sidang,
        'ruangan' => $target->ruangan,
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ]);

    $response->assertSessionHasErrors('dosens');
    expect(session('errors')->first('dosens'))->toContain('Grup Lain');
    expect($target->fresh()->jam_mulai->format('H:i'))->toBe('08:00');
});

test('update yang hanya mengubah ruangan mengabaikan dirinya sendiri', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $target->dosens()->attach($dosen->id);
    $jenis = JenisSidang::factory()->create();

    $this->actingAs($admin)->put(route('admin.schedules.update', $target), [
        'nama_grup_sidang' => $target->nama_grup_sidang,
        'ruangan' => 'Ruang Baru',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [$dosen->id],
    ])->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseHas('schedules', ['id' => $target->id, 'ruangan' => 'Ruang Baru']);
});

test('update menggeser jam hingga mahasiswa ter-plot bentrok ditolak', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '13:00',
        'jam_selesai' => '15:00',
    ]);
    $target->mahasiswas()->attach($mahasiswa->id);
    $lain = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Lain',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '14:00',
        'jam_selesai' => '16:00',
    ]);
    $lain->mahasiswas()->attach($mahasiswa->id);
    $jenis = JenisSidang::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.schedules.update', $target), [
        'nama_grup_sidang' => $target->nama_grup_sidang,
        'ruangan' => $target->ruangan,
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '14:30',
        'jam_selesai' => '16:30',
        'jenis_sidang_id' => $jenis->id,
        'dosens' => [],
    ]);

    $response->assertSessionHasErrors('mahasiswas');
    expect(session('errors')->first('mahasiswas'))->toContain('Grup Lain');
    expect($target->fresh()->jam_mulai->format('H:i'))->toBe('13:00');
});

test('plot mahasiswa ke jadwal overlap dengan jadwal lain ditolak', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
    ]);
    $lain = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Lain',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $lain->mahasiswas()->attach($mahasiswa->id);

    $response = $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $target), [
        'user_id' => $mahasiswa->id,
    ]);

    $response->assertSessionHasErrors('user_id');
    expect(session('errors')->first('user_id'))->toContain('Grup Lain');
    $this->assertDatabaseMissing('schedule_mahasiswa', [
        'schedule_id' => $target->id,
        'user_id' => $mahasiswa->id,
    ]);
});

test('plot mahasiswa ke jadwal jam menyambung diterima', function () {
    $admin = User::factory()->admin()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $target = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '11:00',
        'jam_selesai' => '13:00',
    ]);
    $lain = Schedule::factory()->create([
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $lain->mahasiswas()->attach($mahasiswa->id);

    $this->actingAs($admin)->post(route('admin.schedules.mahasiswa.store', $target), [
        'user_id' => $mahasiswa->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('schedule_mahasiswa', [
        'schedule_id' => $target->id,
        'user_id' => $mahasiswa->id,
    ]);
});
```

- [ ] **Step 2: Jalankan test, pastikan 4 pertama gagal**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/AdminScheduleConflictTest.php
```
Expected: FAIL pada test store-bentrok, update-geser-dosen, update-geser-mahasiswa, plot-overlap (tidak ada error konflik); test "tanggal berbeda", "ignore self", "jam nempel" sudah lulus karena belum ada validasi.

- [ ] **Step 3: Implementasi `withValidator` di kedua FormRequest**

Di `app/Http/Requests/StoreScheduleRequest.php` — tambah `use` di atas (setelah namespace):

```php
use App\Services\ScheduleConflictService;
use Illuminate\Validation\Validator;
```

lalu tambah method (di bawah `messages()`):

```php
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(ScheduleConflictService::class);
            $conflicts = $service->findDosenConflicts(
                array_map('intval', (array) $this->input('dosens', [])),
                (string) $this->input('tanggal_sidang'),
                (string) $this->input('jam_mulai'),
                (string) $this->input('jam_selesai'),
            );

            foreach ($conflicts as $entry) {
                foreach ($service->describeConflict($entry) as $message) {
                    $validator->errors()->add('dosens', $message);
                }
            }
        });
    }
```

Di `app/Http/Requests/UpdateScheduleRequest.php` — tambah `use` yang sama, lalu method:

```php
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schedule = $this->route('schedule');
            $service = app(ScheduleConflictService::class);

            $conflicts = $service->findDosenConflicts(
                array_map('intval', (array) $this->input('dosens', [])),
                (string) $this->input('tanggal_sidang'),
                (string) $this->input('jam_mulai'),
                (string) $this->input('jam_selesai'),
                $schedule->id,
            );

            foreach ($conflicts as $entry) {
                foreach ($service->describeConflict($entry) as $message) {
                    $validator->errors()->add('dosens', $message);
                }
            }

            $mahasiswaConflicts = $service->findMahasiswaConflicts(
                $schedule->mahasiswas()->pluck('users.id')->all(),
                (string) $this->input('tanggal_sidang'),
                (string) $this->input('jam_mulai'),
                (string) $this->input('jam_selesai'),
                $schedule->id,
            );

            foreach ($mahasiswaConflicts as $entry) {
                foreach ($service->describeConflict($entry) as $message) {
                    $validator->errors()->add('mahasiswas', $message);
                }
            }
        });
    }
```

Catatan: `$this->input('tanggal_sidang')` string `Y-m-d`; kolom DATE dibandingkan via `whereDate` — aman. `jam_*` string `H:i`.

- [ ] **Step 4: Jalankan semua test terkait, pastikan lulus**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/AdminScheduleConflictTest.php tests/Feature/AdminScheduleTest.php tests/Feature/ScheduleConflictServiceTest.php
```
Expected: PASS semua (termasuk 25 test lama AdminScheduleTest — tidak boleh ada regresi).

---

### Task 3: Validasi plotting mahasiswa (`StoreScheduleMahasiswaRequest`)

**Files:**
- Modify: `app/Http/Requests/StoreScheduleMahasiswaRequest.php`
- Test: `tests/Feature/AdminScheduleConflictTest.php` (sudah dibuat di Task 2, test plot-overlap & jam-nempel)

**Interfaces:**
- Consumes: `ScheduleConflictService` (Task 1).
- Produces: error key `user_id` dengan pesan konflik saat mahasiswa bentrok.

- [ ] **Step 1: Pastikan test plot-overlap masih gagal (red)**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/AdminScheduleConflictTest.php --filter="overlap"
```
Expected: FAIL `plot mahasiswa ke jadwal overlap dengan jadwal lain ditolak` (belum ada validasi).

- [ ] **Step 2: Implementasi**

Di `app/Http/Requests/StoreScheduleMahasiswaRequest.php` — tambah `use` di atas:

```php
use App\Services\ScheduleConflictService;
use Illuminate\Validation\Validator;
```

lalu tambah method di bawah `messages()`:

```php
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schedule = $this->route('schedule');
            $service = app(ScheduleConflictService::class);
            $conflicts = $service->findMahasiswaConflicts(
                [$this->integer('user_id')],
                $schedule->tanggal_sidang->toDateString(),
                $schedule->jam_mulai->format('H:i'),
                $schedule->jam_selesai->format('H:i'),
            );

            foreach ($conflicts as $entry) {
                foreach ($service->describeConflict($entry) as $message) {
                    $validator->errors()->add('user_id', $message);
                }
            }
        });
    }
```

- [ ] **Step 3: Jalankan test, pastikan lulus**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/AdminScheduleConflictTest.php tests/Feature/AdminScheduleTest.php
```
Expected: PASS semua.

---

### Task 4: Cek konflik di import CSV (`ScheduleImport`)

**Files:**
- Modify: `app/Imports/ScheduleImport.php`
- Test: `tests/Feature/ScheduleImportTest.php` (tambah 2 test)

**Interfaces:**
- Consumes: `ScheduleConflictService` (Task 1).
- Produces: row bentrok → `$failures[] = 'Baris N: <pesan>'`; controller meng-flash `session('error')` berisi pesan tersebut.

- [ ] **Step 1: Tambah 2 test di akhir `tests/Feature/ScheduleImportTest.php`**

```php
test('import: baris bentrok dengan jadwal database masuk failures', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();
    $existing = Schedule::factory()->create([
        'nama_grup_sidang' => 'Grup Pagi',
        'tanggal_sidang' => '2026-08-20',
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $existing->dosens()->attach($dosen->id);

    $csv = "nama_grup_sidang,ruangan,tanggal_sidang,jam_mulai,jam_selesai,dosen_ids\n";
    $csv .= "Grup Bentrok,Ruang A,2026-08-20,10:00,12:00,{$dosen->id}\n";
    $csv .= "Grup Aman,Ruang B,2026-08-21,09:00,11:00,\n";

    $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

    $response = $this->actingAs($admin)->post(route('admin.schedules.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseHas('schedules', ['nama_grup_sidang' => 'Grup Aman']);
    $this->assertDatabaseMissing('schedules', ['nama_grup_sidang' => 'Grup Bentrok']);
    expect(session('error'))->toContain('Baris 2')->toContain('Grup Pagi');
});

test('import: dua baris dalam satu file saling bentrok', function () {
    $admin = User::factory()->admin()->create();
    $dosen = User::factory()->dosen()->create();

    $csv = "nama_grup_sidang,ruangan,tanggal_sidang,jam_mulai,jam_selesai,dosen_ids\n";
    $csv .= "Grup Satu,Ruang A,2026-08-20,09:00,11:00,{$dosen->id}\n";
    $csv .= "Grup Dua,Ruang B,2026-08-20,10:00,12:00,{$dosen->id}\n";

    $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csv);

    $response = $this->actingAs($admin)->post(route('admin.schedules.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseHas('schedules', ['nama_grup_sidang' => 'Grup Satu']);
    $this->assertDatabaseMissing('schedules', ['nama_grup_sidang' => 'Grup Dua']);
    expect(session('error'))->toContain('Baris 3');
});
```

Tambahkan `use App\Models\Schedule;` di bagian atas file test (di bawah `use App\Models\User;`).

- [ ] **Step 2: Jalankan test baru, pastikan keduanya gagal**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/ScheduleImportTest.php
```
Expected: 2 test baru FAIL (baris bentrok tetap masuk DB); 1 test lama PASS.

- [ ] **Step 3: Ubah `ScheduleImport`**

Ganti isi `collection()` dan tambahkan method private `guardConflicts` di `app/Imports/ScheduleImport.php` (tambahkan `use App\Services\ScheduleConflictService;` di blok use):

```php
    public function collection(Collection $rows): void
    {
        $dosenIds = User::where('role', 'dosen')->pluck('id')->toArray();
        $service = app(ScheduleConflictService::class);
        $accepted = [];

        foreach ($rows as $i => $row) {
            try {
                DB::beginTransaction();

                $jenisSidang = null;
                $jenisName = trim((string) ($row['jenis_sidang'] ?? ''));
                if ($jenisName !== '') {
                    $jenisSidang = JenisSidang::where('nama', $jenisName)->first();
                    if (! $jenisSidang) {
                        throw new \Exception("Jenis sidang \"{$jenisName}\" tidak ditemukan.");
                    }
                }

                $tanggal = Carbon::parse($row['tanggal_sidang'])->toDateString();
                $jamMulai = Carbon::parse($row['jam_mulai'])->format('H:i');
                $jamSelesai = Carbon::parse($row['jam_selesai'])->format('H:i');

                $candidateIds = [];
                $dosenCol = $row['dosen_ids'] ?? null;
                if ($dosenCol) {
                    $ids = array_filter(array_map('trim', explode(',', $dosenCol)));
                    $candidateIds = array_values(array_intersect($ids, $dosenIds));
                }

                $this->guardConflicts($service, $candidateIds, $tanggal, $jamMulai, $jamSelesai, $accepted);

                $schedule = Schedule::create([
                    'nama_grup_sidang' => $row['nama_grup_sidang'],
                    'ruangan' => $row['ruangan'],
                    'tanggal_sidang' => $tanggal,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'jenis_sidang_id' => $jenisSidang?->id,
                ]);

                if ($candidateIds) {
                    $schedule->dosens()->sync($candidateIds);
                }

                DB::commit();

                $accepted[] = [
                    'tanggal' => $tanggal,
                    'mulai' => $jamMulai,
                    'selesai' => $jamSelesai,
                    'dosen_ids' => $candidateIds,
                ];
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->failures[] = 'Baris '.($i + 2).': '.$e->getMessage();
            }
        }
    }

    /**
     * @param  list<string>  $accepted
     */
    private function guardConflicts(ScheduleConflictService $service, array $candidateIds, string $tanggal, string $jamMulai, string $jamSelesai, array $accepted): void
    {
        if ($candidateIds === []) {
            return;
        }

        $conflicts = $service->findDosenConflicts($candidateIds, $tanggal, $jamMulai, $jamSelesai);

        foreach ($conflicts as $entry) {
            foreach ($service->describeConflict($entry) as $message) {
                throw new \Exception($message);
            }
        }

        foreach ($accepted as $row) {
            if ($row['tanggal'] === $tanggal
                && $row['mulai'] < $jamSelesai
                && $row['selesai'] > $jamMulai
                && count(array_intersect($row['dosen_ids'], $candidateIds)) > 0
            ) {
                throw new \Exception('Konflik dengan baris lain dalam file import (jadwal overlap dengan dosen yang sama).');
            }
        }
    }
```

Perilaku sama dengan kode lama untuk kasus non-konflik (parsing, jenis sidang, sync dosen); hanya ditambah guard sebelum `Schedule::create`.

- [ ] **Step 4: Jalankan test import, pastikan lulus**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/ScheduleImportTest.php
```
Expected: PASS 3 test.

---

### Task 5: Blok error di view create/edit jadwal admin

**Files:**
- Modify: `resources/views/admin/schedules/create.blade.php`
- Modify: `resources/views/admin/schedules/edit.blade.php`

**Interfaces:**
- Consumes: error key `dosens` (Task 2) dan `user_id` (edit sudah punya `@error('user_id')` di baris ~170).
- Produces: ringkasan semua error tampil di atas form; error `dosens` tampil di bawah section dosen.

- [ ] **Step 1: `create.blade.php` — tambah ringkasan error setelah `<h1>`**

Setelah baris `<h1 class="mb-4 ...">Tambah Jadwal Sidang</h1>` sisipkan:

```blade
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-error-500/20 bg-error-50 p-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <ul class="mb-0 list-disc space-y-1 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
```

- [ ] **Step 2: `create.blade.php` — error `dosens` di bawah section dosen**

Cari `<div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Klik hasil untuk menambahkan. Klik × pada tag untuk menghapus.</div>` lalu tambahkan tepat setelahnya:

```blade
            @error('dosens') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
```

- [ ] **Step 3: Ulangi Step 1 & 2 di `edit.blade.php`**

Baca file dulu. Sisipkan blok ringkasan yang sama tepat setelah elemen `<h1 ...>` di `@section('content')`, dan `@error('dosens')` yang sama setelah hint dosen `<div class="mt-1.5 text-xs ...">Klik hasil untuk menambahkan. Klik × pada tag untuk menghapus.</div>` (struktur edit identik dengan create; halaman edit adalah target redirect-back `storeMahasiswa`, dan `@error('user_id')` untuk plotting sudah ada).

- [ ] **Step 4: Verifikasi render + test halaman**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature/AdminScheduleTest.php --filter="menampilkan"
npm run build
```
Expected: semua test render PASS (compile blade OK), build Vite sukses.

---

### Task 6: Verifikasi penuh + restore DB + catat dokumen

**Files:**
- Modify: `docs/ROADMAP.md` (centang Tahap 5)
- Modify: `docs/MEMORY.md` (status snapshot + entri Log Sesi baru di atas)

- [ ] **Step 1: Suite penuh**

```bash
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature
```
Expected: PASS semua (254 lama + ~17 baru), flaky pre-existing `AssistantToolsTest::getStalledRevisions` boleh satu-satunya yang gagal bila muncul (timing ceil — bukan regresi fitur ini).

- [ ] **Step 2: Lint & format**

```bash
vendor/bin/pint --dirty --format agent
npm run lint
```
Expected: bersih (Pint boleh memperbaiki otomatis; jalankan ulang suite bila ada file PHP yang berubah).

- [ ] **Step 3: Restore DB dev**

```bash
php artisan migrate:fresh --seed
```
Expected: seeder jalan, data demo kembali (tanpa backup-file restore; struktur tidak berubah sehingga migrate:fresh cukup).

- [ ] **Step 4: Update ROADMAP.md**

Centang keenam item Tahap 5 menjadi `- [x]`.

- [ ] **Step 5: Update MEMORY.md**

Bagian "Status Saat Ini": tambah ringkasan fitur Deteksi Konflik Jadwal selesai. Bagian "Log Sesi": entri baru di paling atas (tanggal 2026-08-24) — ringkasan, keputusan (tanpa master Ruang, blokir total, half-open interval, import intra-batch), file diubah, verifikasi, catatan sesi berikutnya (**semua perubahan BELUM di-commit — user melarang commit sesi ini**).

---

## Self-Review

- **Spec coverage:** service (T1), store/update dosen+geser-jam mahasiswa (T2), plot mahasiswa (T3), import DB+intra-batch (T4), blok error view + `@error('dosens')` (T5), testing lengkap positif/negatif (T1–T4), ROADMAP/MEMORY (T6). ✔
- **Placeholder scan:** tidak ada TBD/TODO; semua langkah berisi kode/anchor eksak. ✔
- **Type consistency:** `findDosenConflicts`/`findMahasiswaConflicts`/`describeConflict` dipakai konsisten di T2/T3/T4 dengan signature T1; error keys `dosens`/`mahasiswas`/`user_id` konsisten antara FormRequest dan view/test. ✔
