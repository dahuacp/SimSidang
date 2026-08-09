# Dark Mode Toggle + Dashboard Analitik — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add dark mode toggle and 4 analytics charts to the admin dashboard.

**Architecture:** Port Metis dark mode SCSS + Alpine.js toggle. Install ApexCharts, extend DashboardController with 4 aggregate queries, render charts via Alpine.js in Blade view.

**Tech Stack:** Bootstrap 5.3 (data-bs-theme), Alpine.js 3.x, ApexCharts 6.7, Laravel Blade, Pest for tests.

## Global Constraints

- Yellow accent `$primary: #F5B400` — chart colors must complement
- No new Composer packages — ApexCharts via npm only
- All chart labels in Bahasa Indonesia
- `declare(strict_types=1)` on all PHP files
- PSR-12 via Laravel Pint
- Tests via Pest with SQLite `:memory:`
- Follow existing code conventions (check sibling files)

---

## Part A: Dark Mode Toggle

### Task 1: Port dark mode SCSS from Metis

**Files:**
- Create: `resources/scss/themes/_dark.scss`
- Create: `resources/scss/themes/_light.scss`
- Modify: `resources/scss/abstracts/_variables.scss`
- Modify: `resources/scss/app.scss`

**Interfaces:**
- Consumes: Metis reference at `_reference/metis-template/src-modern/styles/scss/themes/`
- Produces: Theme SCSS that applies via `[data-bs-theme]` attribute

- [ ] **Step 1: Create dark theme SCSS**

Create `resources/scss/themes/_dark.scss` with content ported from `_reference/metis-template/src-modern/styles/scss/themes/_dark.scss`, adapted for SIMSIDANG's yellow accent:

```scss
[data-bs-theme="dark"] {
  --bs-body-bg: #{$gray-900};
  --bs-body-color: #{$gray-100};
  --bs-border-color: #{$gray-800};

  .sidebar {
    background-color: #{$gray-900};
    border-color: #{$gray-800};
  }

  .card {
    background-color: rgba(#{to-rgb($gray-900)}, 0.8);
    border-color: #{$gray-800};
  }

  .navbar, .header {
    background-color: rgba(#{to-rgb($gray-900)}, 0.9);
    backdrop-filter: blur(10px);
    border-color: #{$gray-800};
  }
}
```

- [ ] **Step 2: Create light theme SCSS**

Create `resources/scss/themes/_light.scss`:

```scss
[data-bs-theme="light"] {
  --bs-body-bg: #{$gray-50};
  --bs-body-color: #{$gray-900};

  .sidebar {
    background-color: #{$white};
    border-color: #{$gray-200};
  }

  .card {
    background-color: #{$white};
  }

  .navbar, .header {
    background-color: rgba(#{to-rgb($white)}, 0.9);
    backdrop-filter: blur(10px);
    border-color: #{$gray-200};
  }
}
```

- [ ] **Step 3: Add dark token overrides to variables**

Add at the end of `resources/scss/abstracts/_variables.scss`:

```scss
[data-bs-theme="dark"] {
  --bs-link-color: #{lighten($primary, 20%)};
  --bs-link-hover-color: #{lighten($primary, 30%)};
}
```

- [ ] **Step 4: Import themes in app.scss**

Add at the end of `resources/scss/app.scss` (after all component imports):

```scss
@import "themes/dark";
@import "themes/light";
```

- [ ] **Step 5: Verify build**

Run: `npm run build`
Expected: Build succeeds without errors

- [ ] **Step 6: Commit**

```bash
git add resources/scss/themes/ resources/scss/abstracts/_variables.scss resources/scss/app.scss
git commit -m "feat: port dark mode SCSS from Metis template"
```

---

### Task 2: Add Alpine.js themeSwitch + header toggle

**Files:**
- Modify: `resources/js/app.js`
- Modify: `resources/views/layouts/app.blade.php`

**Interfaces:**
- Consumes: Task 1 SCSS (data-bs-theme attribute)
- Produces: `themeSwitch` Alpine.js component, toggle button in header

- [ ] **Step 1: Add themeSwitch Alpine.data to app.js**

Add before the `Alpine.start()` call in `resources/js/app.js`:

```javascript
Alpine.data('themeSwitch', () => ({
    currentTheme: 'light',
    init() {
        this.currentTheme = localStorage.getItem('theme')
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
    },
    toggle() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
        localStorage.setItem('theme', this.currentTheme);
    }
}));
```

- [ ] **Step 2: Add toggle button to header in layout**

In `resources/views/layouts/app.blade.php`, find the header section and add the toggle button. Look for the header/navbar area and add before the user dropdown or at the end of the header:

```html
<div x-data="themeSwitch" x-init="init()" class="d-flex align-items-center">
    <button @click="toggle()" class="btn btn-sm btn-ghost" data-bs-toggle="tooltip" title="Ganti tema">
        <i class="bi bi-sun-fill" x-show="currentTheme === 'light'"></i>
        <i class="bi bi-moon-fill" x-show="currentTheme === 'dark'"></i>
    </button>
</div>
```

- [ ] **Step 3: Verify in browser**

Run: `npm run dev` and `php artisan serve`
Expected: Toggle button visible in header, clicks switch theme, persists on reload

- [ ] **Step 4: Commit**

```bash
git add resources/js/app.js resources/views/layouts/app.blade.php
git commit -m "feat: add dark mode toggle to header"
```

---

## Part B: Dashboard Analitik

### Task 3: Install ApexCharts + create modular entry

**Files:**
- Modify: `package.json`
- Create: `resources/js/apex.js`
- Modify: `vite.config.js`

**Interfaces:**
- Consumes: Metis reference `_reference/metis-template/src-modern/scripts/utils/apex.js`
- Produces: ApexCharts available as global + modular imports

- [ ] **Step 1: Install apexcharts**

Run: `npm install apexcharts`

- [ ] **Step 2: Create modular apex entry**

Create `resources/js/apex.js`:

```javascript
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

export default ApexCharts;
```

- [ ] **Step 3: Add vendor chunk to vite.config.js**

In `vite.config.js`, add `rollupOptions.output.manualChunks` for apexcharts:

```javascript
manualChunks: {
    'vendor-charts': ['apexcharts'],
}
```

- [ ] **Step 4: Verify build**

Run: `npm run build`
Expected: Build succeeds, `vendor-charts.js` chunk created in build output

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json resources/js/apex.js vite.config.js
git commit -m "feat: install ApexCharts and create modular entry"
```

---

### Task 4: Extend DashboardController with chart data

**Files:**
- Modify: `app/Http/Controllers/Admin/DashboardController.php`
- Create: `tests/Feature/AdminDashboardAnalyticsTest.php`

**Interfaces:**
- Consumes: existing models (Submission, Schedule, RevisionNote, SubmissionStatusLog)
- Produces: 4 chart datasets passed to view

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AdminDashboardAnalyticsTest.php`:

```php
<?php

use App\Models\RevisionNote;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\SubmissionStatusLog;
use App\Models\User;

test('admin dashboard menampilkan data analitik', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'revisi',
    ]);
    $dosen = User::factory()->dosen()->create();
    RevisionNote::factory()->create([
        'submission_id' => $submission->id,
        'dosen_id' => $dosen->id,
        'status_poin' => 'open',
    ]);
    SubmissionStatusLog::factory()->create([
        'submission_id' => $submission->id,
        'status_baru' => 'revisi',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertViewHas('submissionStatus');
    $response->assertViewHas('scheduleSubmissions');
    $response->assertViewHas('revisionStats');
    $response->assertViewHas('statusTrend');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter="AdminDashboardAnalytics"`
Expected: FAIL — view does not pass these variables

- [ ] **Step 3: Implement chart data in controller**

Edit `app/Http/Controllers/Admin/DashboardController.php` — add 4 queries after existing queries:

```php
use Illuminate\Support\Facades\DB;

// Inside index() method, after existing $totalSubmissions and $revisiTerbuka:

$submissionStatus = Submission::groupBy('status')
    ->pluck(DB::raw('count(*)'), 'status')
    ->toArray();

$scheduleSubmissions = Schedule::withCount('submissions')
    ->orderBy('tanggal_sidang')
    ->get();

$revisionStats = RevisionNote::groupBy('status_poin')
    ->pluck(DB::raw('count(*)'), 'status_poin')
    ->toArray();

$statusTrend = SubmissionStatusLog::selectRaw('DATE(created_at) as date, status_baru, count(*) as total')
    ->groupBy('date', 'status_baru')
    ->orderBy('date')
    ->get()
    ->groupBy('date')
    ->map(fn ($items) => $items->pluck('total', 'status_baru'))
    ->toArray();
```

Pass to view:

```php
return view('admin.dashboard', compact(
    // ... existing vars ...
    'submissionStatus',
    'scheduleSubmissions',
    'revisionStats',
    'statusTrend',
));
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter="AdminDashboardAnalytics"`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/DashboardController.php tests/Feature/AdminDashboardAnalyticsTest.php
git commit -m "feat: add chart data queries to DashboardController"
```

---

### Task 5: Add charts to dashboard Blade view

**Files:**
- Modify: `resources/views/admin/dashboard.blade.php`

**Interfaces:**
- Consumes: Task 4 view variables (submissionStatus, scheduleSubmissions, revisionStats, statusTrend)
- Produces: 4 rendered ApexCharts

- [ ] **Step 1: Add chart row 1 (Status Submission + Submission per Jadwal)**

In `resources/views/admin/dashboard.blade.php`, after the stat cards row and before the schedule table, add:

```html
{{-- Chart Row 1 --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Status Submission</h5>
            </div>
            <div class="card-body">
                <div id="chart-status-submission"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Submission per Jadwal</h5>
            </div>
            <div class="card-body">
                <div id="chart-schedule-submissions"></div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Add chart row 2 (Revisi + Tren Status)**

```html
{{-- Chart Row 2 --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Revisi Open vs Resolved</h5>
            </div>
            <div class="card-body">
                <div id="chart-revision-stats"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Tren Status per Hari</h5>
            </div>
            <div class="card-body">
                <div id="chart-status-trend"></div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Add chart initialization script**

At the bottom of the same Blade file, add `@push('scripts')` or inline script:

```html
@push('scripts')
<script type="module">
import ApexCharts from 'apexcharts';

// Chart 1: Status Submission (Donut)
const statusData = @json($submissionStatus);
const statusLabels = Object.keys(statusData);
const statusValues = Object.values(statusData);
new ApexCharts(document.querySelector('#chart-status-submission'), {
    chart: { type: 'donut', height: 300 },
    labels: statusLabels,
    series: statusValues,
    colors: ['#ffc107', '#17a2b8', '#0d6efd', '#28a745'],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true, formatter: (val) => val + '%' }
}).render();

// Chart 2: Submission per Jadwal (Bar)
const scheduleData = @json($scheduleSubmissions);
const scheduleNames = scheduleData.map(s => s.nama_grup_sidang);
const scheduleCounts = scheduleData.map(s => s.submissions_count);
new ApexCharts(document.querySelector('#chart-schedule-submissions'), {
    chart: { type: 'bar', height: 300 },
    series: [{ name: 'Submission', data: scheduleCounts }],
    xaxis: { categories: scheduleNames },
    colors: ['#F5B400'],
    legend: { show: false }
}).render();

// Chart 3: Revisi Open vs Resolved (Donut)
const revisionData = @json($revisionStats);
new ApexCharts(document.querySelector('#chart-revision-stats'), {
    chart: { type: 'donut', height: 300 },
    labels: ['Open', 'Resolved'],
    series: [revisionData['open'] ?? 0, revisionData['resolved'] ?? 0],
    colors: ['#dc3545', '#28a745'],
    legend: { position: 'bottom' }
}).render();

// Chart 4: Tren Status per Hari (Line)
const trendData = @json($statusTrend);
const dates = Object.keys(trendData);
const statusMap = {
    pending: { label: 'Pending', color: '#ffc107' },
    sidang_berjalan: { label: 'Sidang Berjalan', color: '#17a2b8' },
    revisi: { label: 'Revisi', color: '#0d6efd' },
    selesai: { label: 'Selesai', color: '#28a745' }
};
const series = Object.entries(statusMap).map(([key, cfg]) => ({
    name: cfg.label,
    data: dates.map(d => trendData[d]?.[key] || 0)
}));
const seriesColors = Object.values(statusMap).map(s => s.color);

new ApexCharts(document.querySelector('#chart-status-trend'), {
    chart: { type: 'area', height: 300 },
    series: series,
    xaxis: { categories: dates },
    colors: seriesColors,
    stroke: { curve: 'smooth' },
    legend: { position: 'bottom' }
}).render();
</script>
@endpush
```

- [ ] **Step 4: Verify in browser**

Run: `npm run dev` and `php artisan serve`
Expected: 4 charts render correctly on admin dashboard with real data

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/dashboard.blade.php
git commit -m "feat: add 4 analytics charts to admin dashboard"
```

---

### Task 6: Final verification + cleanup

**Files:**
- No new files

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass (existing + new analytics test)

- [ ] **Step 2: Run pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: PHP files formatted

- [ ] **Step 3: Run lint**

Run: `npm run lint`
Expected: No errors

- [ ] **Step 4: Run build**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 5: Update MEMORY.md + ROADMAP.md**

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "feat: complete dark mode toggle + dashboard analytics (Tahap 3)"
```
