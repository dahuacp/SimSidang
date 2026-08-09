# Design Spec: Dark Mode Toggle + Dashboard Analitik

> Tahap 3 — Fitur Lanjutan SIMSIDANG

---

## Overview

Dua fitur UI untuk meningkatkan pengalaman admin:
1. **Dark mode toggle** — porting dark mode dari Metis template
2. **Dashboard analitik** — 4 chart ApexCharts di admin dashboard

---

## 1. Dark Mode Toggle

### Goal
Admin dapat beralih antara light dan dark theme. Theme dipersist di localStorage.

### Approach
Port langsung dari Metis template yang sudah punya full dark mode support (Bootstrap 5.3 `data-bs-theme`).

### Components

#### SCSS Themes (port dari Metis)
- `resources/scss/themes/_dark.scss` — overrides untuk `[data-bs-theme="dark"]`:
  - Body bg: `$gray-900`, body color: `$gray-100`
  - Sidebar: dark bg
  - Cards: semi-transparent dark bg
  - Header: semi-transparent dark bg + backdrop blur
  - Border color: `$gray-800`
- `resources/scss/themes/_light.scss` — overrides untuk `[data-bs-theme="light"]`:
  - Body bg: `$gray-50`, body color: `$gray-900`
  - Standard light colors

#### SCSS Variables
- Tambah `[data-bs-theme="dark"]` token overrides di `resources/scss/abstracts/_variables.scss`:
  - Text emphasis tokens (lighter colors for dark bg)
  - Sudah ada di Metis, tinggal port

#### Alpine.js Toggle Component
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

#### Header Button
Di `layouts/app.blade.php`, tambah tombol toggle di header:
```html
<div x-data="themeSwitch" x-init="init()">
    <button @click="toggle()" class="btn btn-sm">
        <i class="bi bi-sun-fill" x-show="currentTheme === 'light'"></i>
        <i class="bi bi-moon-fill" x-show="currentTheme === 'dark'"></i>
    </button>
</div>
```

### Files
| File | Action |
|---|---|
| `resources/scss/themes/_dark.scss` | NEW — port dari Metis |
| `resources/scss/themes/_light.scss` | NEW — port dari Metis |
| `resources/scss/abstracts/_variables.scss` | EDIT — tambah dark token overrides |
| `resources/scss/app.scss` | EDIT — import themes |
| `resources/views/layouts/app.blade.php` | EDIT — toggle button + x-init |
| `resources/js/app.js` | EDIT — tambah themeSwitch Alpine.data |

### Testing
Manual verification — UI-only feature, no backend logic.

---

## 2. Dashboard Analitik

### Goal
Admin dashboard menampilkan 4 chart aggregasi untuk memantau kondisi sidang.

### Charts

#### Chart 1: Status Submission (Donut)
- **Data:** `Submission::groupBy('status')->pluck('count(*)', 'status')`
- **Labels:** pending, sidang_berjalan, revisi, selesai
- **Colors:** Bootstrap theme colors (warning, info, primary, success)
- **Position:** Row 1, col 1

#### Chart 2: Submission per Jadwal (Bar)
- **Data:** `Schedule::withCount('submissions')->orderBy('tanggal_sidang')->get()`
- **X-axis:** nama_grup_sidang
- **Y-axis:** submissions_count
- **Position:** Row 1, col 2

#### Chart 3: Revisi Open vs Resolved (Donut)
- **Data:** `RevisionNote::groupBy('status_poin')->pluck('count(*)', 'status_poin')`
- **Labels:** open, resolved
- **Colors:** danger (open), success (resolved)
- **Position:** Row 2, col 1

#### Chart 4: Tren Status per Hari (Line/Area)
- **Data:** `SubmissionStatusLog::selectRaw('DATE(created_at) as date, status_baru, count(*) as total')->groupBy('date', 'status_baru')->orderBy('date')->get()`
- **X-axis:** date
- **Series:** one line per status
- **Position:** Row 2, col 2

### Approach
Server-side data rendering via Blade. Data di-query di controller, di-encode ke JSON, di-pass ke Alpine.js chart component.

### Controller Changes
`Admin\DashboardController@index` — tambah 4 query, pass ke view:
```php
$submissionStatus = Submission::groupBy('status')->pluck(DB::raw('count(*)'), 'status');
$scheduleSubmissions = Schedule::withCount('submissions')->orderBy('tanggal_sidang')->get();
$revisionStats = RevisionNote::groupBy('status_poin')->pluck(DB::raw('count(*)'), 'status_poin');
$statusTrend = SubmissionStatusLog::selectRaw('DATE(created_at) as date, status_baru, count(*) as total')
    ->groupBy('date', 'status_baru')->orderBy('date')->get();
```

### Frontend
- Install `apexcharts` via npm
- Create `resources/js/apex.js` — modular entry (port dari Metis reference)
- Update `vite.config.js` — add vendor chunk for apexcharts
- Chart instances init via Alpine.js `x-init`

### View Layout
```
[Stat Cards Row — existing 4 cards]

[Chart Row 1]
  [Status Submission (donut)]  [Submission per Jadwal (bar)]

[Chart Row 2]
  [Revisi Open/Resolved (donut)]  [Tren Status per Hari (line)]

[Schedule Table — existing]
```

### Files
| File | Action |
|---|---|
| `package.json` | EDIT — +apexcharts |
| `resources/js/apex.js` | NEW — modular chart entry |
| `vite.config.js` | EDIT — vendor chunk |
| `app/Http/Controllers/Admin/DashboardController.php` | EDIT — tambah 4 queries |
| `resources/views/admin/dashboard.blade.php` | EDIT — tambah chart rows |

### Testing
- Feature test: dashboard returns 200 with chart data in view
- Feature test: query methods return expected data structure

---

## Constraints

- **Yellow accent:** `$primary: #F5B400` — chart colors harus complement kuning
- **No new Composer packages** — ApexCharts via npm only
- **Read-only dashboard** — tidak ada interaksi CRUD di chart
- **Indonesian labels** — semua label chart dalam Bahasa Indonesia
