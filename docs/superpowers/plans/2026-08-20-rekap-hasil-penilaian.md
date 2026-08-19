# Rekap Hasil Penilaian — Implementation Plan

**Goal:** Build admin report page for aggregated assessment results with Excel export.

**Architecture:** Service layer for calculation, controller for HTTP, Blade view + ApexCharts for UI, Excel Sheet class for export.

**Tech Stack:** Laravel 13, PHP 8.4, MySQL 8, Maatwebsite/Excel, Barryvdh/DomPDF, ApexCharts 6.x

## Global Constraints

- Login via Fortify `username` (NIM/NIDN), RBAC via Gate
- Form Request for validation (validation terpusat)
- File upload: submission PDF ≤10MB, attachments ≤5MB
- RBAC: admin boleh lihat semua, dosen mahasiswa read-only
- Semua query diuji pakai SQLite :memory: + RefreshDatabase
- Error messages Bahasa Indonesia

---

## File Structure

| File | Status | Purpose |
|------|--------|---------|
| `config/penilaian.php` | Create | Weight configuration (dospem %, penguji %) |
| `app/Services/RekapNilaiService.php` | Create | Business logic for computing rekap rows |
| `app/Http/Controllers/Admin/RekapController.php` | Create | index + exportExcel |
| `app/Exports/NilaiRekapExport.php` | Create | Excel sheet class |
| `resources/views/admin/rekap/nilai.blade.php` | Create | Tabel + filter + grafik ApexCharts |
| `tests/Feature/RekapNilaiTest.php` | Create | Test coverage |
| `routes/web.php` | Modify | Add routes |

---

### Task 1: Create config/penilaian.php

**Files:**
- Create: `config/penilaian.php`

**Interfaces:**
- Produces: config key `penilaian.bobot.dospem` (int), `penilaian.bobot.penguji` (int)

- [ ] **Step 1: Write the config file**

```php
<?php

return [
    'bobot' => [
        'dospem' => env('NILAI_DOSPEM_WEIGHT', 50),
        'penguji' => env('NILAI_PENGUJI_WEIGHT', 50),
    ],
];
```

- [ ] **Step 2: Verify file exists**
Run: `cat config/penilaian.php`
Expected: File with bobot array

- [ ] **Step 3: Commit**
```bash
git add config/penilaian.php
git commit -m "config: add penilaian weight configuration for rekap nilai"
```

---

### Task 2: Create RekapNilaiService

**Files:**
- Create: `app/Services/RekapNilaiService.php`
- Create: `tests/Feature/RekapNilaiTest.php`

**Interfaces:**
- Consumes: AssessmentForm, config penilaian.bobot
- Produces: array of rekap rows

- [ ] **Step 1: Write the failing test**

```php
<?php

test('service computes rekap data correctly', function () {
    $admin = User::factory()->admin()->create();
    $s = scenarioCetakNilai();

    $service = app(\App\Services\RekapNilaiService::class);
    $data = $service->getRows();

    expect($data)->not->toBeEmpty();
    expect($data[0]['mahasiswa'])->toBe($s['mahasiswa']->name);
});
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --compact --filter=test_service_computes_rekap_data_correctly`
Expected: FAIL "Class not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

use App\Models\AssessmentForm;

class RekapNilaiService
{
    public function getRows(?int $prodiId = null, string $sort = 'desc'): array
    {
        $weight = config('penilaian.bobot');

        $forms = AssessmentForm::with([
            'submission.user.prodi',
            'submission.schedule.jenisSidang',
            'dosen',
            'template'
        ])
        ->whereHas('submission')
        ->when($prodiId, fn($q, $id) => $q->whereHas('submission.user', fn($q2) => $q2->where('prodi_id', $id)))
        ->get()
        ->groupBy('submission_id');

        $rows = [];
        foreach ($forms as $submissionId => $formGroup) {
            $submission = $formGroup->first()->submission;
            $user = $submission->user;

            $dospemScore = $this->calculateAverageScore($formGroup, 'dospem');
            $pengujiScore = $this->calculateAverageScore($formGroup, 'penguji');
            $totalScore = round($dospemScore * ($weight['dospem'] / 100) + $pengujiScore * ($weight['penguji'] / 100), 1);

            $rows[] = [
                'no' => 0,
                'mahasiswa' => $user->name,
                'nim' => $user->username,
                'prodi' => $user->prodi?->nama ?? '-',
                'judul' => $submission->judul_laporan,
                'dospem_nilai' => $dospemScore > 0 ? $dospemScore : '-',
                'penguji_nilai' => $pengujiScore > 0 ? $pengujiScore : '-',
                'nilai_akhir' => $totalScore,
            ];
        }

        $rows = collect($rows)->sortBy('nilai_akhir', SORT_NUMERIC, $sort === 'asc')->values()->toArray();
        return collect($rows)->map(fn($row, $idx) => $row + ['no' => $idx + 1])->toArray();
    }

    protected function calculatePercentage(float $score, array $items): float
    {
        $maxScore = collect($items)->sum('maksimal');
        return $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : 0;
    }

    protected function calculateAverageScore($formGroup, string $tipe): float
    {
        $filtered = $formGroup->where('tipe_penilai', $tipe);
        if ($filtered->isEmpty()) {
            return 0.0;
        }

        $sum = $filtered->sum(function ($form) {
            return $this->calculatePercentage($form->skor_total, $form->template->items);
        });

        return round($sum / $filtered->count(), 1);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**
Run: `php artisan test --compact --filter=test_service_computes_rekap_data_correctly`
Expected: PASS

- [ ] **Step 5: Commit**
```bash
git add app/Services/RekapNilaiService.php tests/Feature/RekapNilaiTest.php
git commit -m "feat: add RekapNilaiService for aggregated assessment results"
```

---

### Task 3: Create Admin/RekapController

**Files:**
- Create: `app/Http/Controllers/Admin/RekapController.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: RekapNilaiService, query parameters prodi_id, sort
- Produces: View response, Excel download

- [ ] **Step 1: Write test for index endpoint**

```php
test('admin can access rekap nilai page', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.rekap.nilai'))->assertOk();
});

test('admin can export rekap nilai to excel', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.rekap.nilai-excel'))
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --compact --filter=test_admin_can_access_rekap_nilai`
Expected: FAIL "route not defined"

- [ ] **Step 3: Write controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use App\Services\RekapNilaiService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NilaiRekapExport;

class RekapController extends Controller
{
    public function index(Request $request, RekapNilaiService $service)
    {
        $this->authorize('viewAdminMenu', User::class);

        $prodis = Prodi::all();
        $prodiId = $request->input('prodi_id');
        $sort = $request->input('sort', 'desc');

        $rows = $service->getRows($prodiId, $sort);

        return view('admin.rekap.nilai', compact('prodis', 'prodiId', 'sort', 'rows'));
    }

    public function exportExcel(Request $request, RekapNilaiService $service)
    {
        $this->authorize('viewAdminMenu', User::class);

        $prodiId = $request->input('prodi_id');
        $sort = $request->input('sort', 'desc');
        $rows = $service->getRows($prodiId, $sort);

        return Excel::download(new NilaiRekapExport($rows), 'rekap_nilai_'.now()->format('Ymd_His').'.xlsx');
    }
}
```

- [ ] **Step 4: Add routes**

```php
Route::get('/rekap/nilai', [RekapController::class, 'index'])->name('rekap.nilai');
Route::get('/rekap/nilai-excel', [RekapController::class, 'exportExcel'])->name('rekap.nilai-excel');
```

- [ ] **Step 5: Run test to verify it passes**
Run: `php artisan test --compact --filter=test_admin_can_access_rekap_nilai`
Expected: PASS

- [ ] **Step 6: Commit**
```bash
git add app/Http/Controllers/Admin/RekapController.php routes/web.php
git commit -m "feat: add RekapController with nilai index + excel export"
```

---

### Task 4: Create NilaiRekapExport (Excel)

**Files:**
- Create: `app/Exports/NilaiRekapExport.php`

- [ ] **Step 1: Write export class**

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NilaiRekapExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $rows) {}

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return ['No', 'Mahasiswa', 'NIM', 'Progdi', 'Judul Laporan', 'Dospem Nilai', 'Penguji Nilai', 'Nilai Akhir'];
    }

    public function map($row): array
    {
        return [
            $row['no'],
            $row['mahasiswa'],
            $row['nim'],
            $row['prodi'],
            $row['judul'],
            $row['dospem_nilai'],
            $row['penguji_nilai'],
            $row['nilai_akhir'],
        ];
    }
}
```

- [ ] **Step 2: Run excel test**
Run: `php artisan test --compact --filter=test_admin_can_export_rekap_nilai_to_excel`
Expected: PASS

- [ ] **Step 3: Commit**
```bash
git add app/Exports/NilaiRekapExport.php
git commit -m "feat: add NilaiRekapExport for Excel download"
```

---

### Task 5: Create Blade view admin/rekap/nilai.blade.php

**Files:**
- Create: `resources/views/admin/rekap/nilai.blade.php`
- Modify: `resources/views/admin/rekap/index.blade.php` (add link)

**Interfaces:**
- Reuses: sidebar layout, component patterns

- [ ] **Step 1: Write Blade view**

```blade
@extends('layouts.app')

@section('title', 'Rekap Hasil Penilaian')

@section('content')
<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Rekap Hasil Penilaian</h1>
    <div class="flex flex-wrap gap-2">
        <form method="GET" class="flex gap-2" role="search">
            <select name="prodi_id" class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                <option value="">Semua Prodi</option>
                @foreach($prodis as $prodi)
                <option value="{{ $prodi->id }}" {{ request('prodi_id') == $prodi->id ? 'selected' : '' }}>{{ $prodi->nama }}</option>
                @endforeach
            </select>
            <select name="sort" class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                <option value="desc" {{ request('sort') == 'asc' ? '' : 'selected' }}>Nilai Tertinggi</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Nilai Terendah</option>
            </select>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Filter</button>
        </form>
        <a href="{{ route('admin.rekap.nilai-excel', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
            </svg>
            Export Excel
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">No</th>
                    <th class="px-4 py-3">Mahasiswa</th>
                    <th class="px-4 py-3">NIM</th>
                    <th class="px-4 py-3">Progdi</th>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Dospem</th>
                    <th class="px-4 py-3">Penguji</th>
                    <th class="px-4 py-3 font-semibold">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($rows as $row)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-4 py-3">{{ $row['no'] }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{{ $row['mahasiswa'] }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row['nim'] }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['prodi'] }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['judul'] }}</td>
                    <td class="px-4 py-3">{{ $row['dospem_nilai'] !== '-' ? number_format($row['dospem_nilai'], 1) . '%' : '—' }}</td>
                    <td class="px-4 py-3">{{ $row['penguji_nilai'] !== '-' ? number_format($row['penguji_nilai'], 1) . '%' : '—' }}</td>
                    <td class="px-4 py-3 font-semibold"><span class="badge-status">{{ $row['nilai_akhir'] }}%</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data penilaian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
```

- [ ] **Step 2: Update sidebar to link to page**
Edit: `resources/views/layouts/sidebar.blade.php` — add link to Rekap → Hasil Penilaian

- [ ] **Step 3: Run view test**
Run: `php artisan test --compact --filter=test_admin_can_access_rekap_nilai_page`
Expected: PASS (view exists)

- [ ] **Step 4: Commit**
```bash
git add resources/views/admin/rekap/nilai.blade.php resources/views/layouts/sidebar.blade.php
git commit -m "view: add nilai rekap tabel dengan filter prodi"
```

---

### Task 6: Test integration & lint

- [ ] **Step 1: Run full test suite**
Run: `php artisan test --compact`
Expected: All tests pass

- [ ] **Step 2: Run lint & pint**
Run: `npm run lint && vendor/bin/pint`
Expected: Clean

- [ ] **Step 3: Build frontend**
Run: `npm run build`
Expected: No errors

---

## Plan Review Checklist

- [ ] **Spec coverage:** All requirements covered (nilai per mahasiswa, filter prodi, bobot konfigurasi, export Excel, tabel UI)
- [ ] **Type consistency:** Routes, model properties, and view keys match
- [ ] **No placeholders:** All code blocks have real implementations
- [ ] **Boundary conditions:** handled (dospem nullable, prodi filter, sort)

