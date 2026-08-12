# MEMORY.md — Vault Memory Project SIMSIDANG

> File ini adalah memori lintas-sesi untuk agent (lihat protokol wajib di `AGENTS.md` bagian 6).
> **Baca bagian "Status Saat Ini" dulu setiap mulai sesi baru.** Bagian "Log Sesi" adalah histori append-only (entri terbaru di paling atas).

---

## Status Saat Ini
*(Bagian ini di-overwrite/update tiap sesi — bukan log historis, tapi snapshot kondisi terkini project.)*

- **Tahap MVP aktif:** Tahap 1 (MVP inti) SELESAI + Tahap 2 (Peningkatan Operasional) SELESAI + Tahap 3 FR-05 SELESAI + Dark Mode + Dashboard Analitik SELESAI. ROADMAP.md: semua item Tahap 1, 2 & FR-05 Tahap 3 + Dark Mode + Dashboard Analitik dicentang. Sisa: Passkey auth.
- **Fitur terakhir dikerjakan:** Theme color diubah dari kuning ke indigo (`$primary: #6366f1`) sesuai default Metis template. `$warning: #f59e0b` (amber), `$info: #06b6d4` (cyan), `$dark: #1e293b` (slate 800). `$primary-text-emphasis` diubah ke `#ffffff`. `btn-primary` text color diubah ke white. Dokumentasi (AGENTS.md, FRONTEND-GUIDE.md) diupdate.
- **Sebelumnya:** Plotting Mahasiswa ke grup sidang (admin) + view dosen semua jadwal. Pivot table `schedule_mahasiswa`, relasi `Schedule::mahasiswas()`, section "Plotting Mahasiswa" di edit jadwal, kolom "Jml Mahasiswa" di index. Dosen kini lihat SEMUA jadwal (bukan hanya hari ini) + filter tab Semua/Hari Ini + mahasiswa ter-plot tanpa submission ditampilkan berstatus "Belum upload". Fix bug laten: filter tanggal pakai `whereDate()` (cast `date` menyimpan `2026-08-10 00:00:00` → `where(..., 'Y-m-d')` gagal di SQLite).
- **Blocker/isu terbuka:** (1) Warning build Vite: font `bootstrap-icons.woff/woff2` tidak ter-resolve. (2) Git initialized dengan initial commit + feature commits. (3) `laravel boost:mcp` belum dikonfigurasi. (4) Vite/Sass deprecation warnings (Bootstrap 5 legacy, Dart Sass 3.0) — non-blocking.
- **Environment:** Laravel 13.8.0, PHP 8.4.23, MariaDB 11.8.6 (MySQL-compatible), DB `sidangapp2`/user `sidang`/pass `sidang` @ 127.0.0.1:3306. `APP_NAME=SISIDANG`, `APP_LOCALE=id`, `FILESYSTEM_DISK=local`. Packages: `maatwebsite/excel` ^3.1, `barryvdh/laravel-dompdf` ^3.1, `apexcharts` ^6.7.0 (npm).
- **Seed:** admin `telo`/`kaspe`, 4 dosen, 6 mahasiswa, 4 schedules, 6 submissions, 2 revision notes. `migrate:fresh` sukses; semua migration termasuk Tahap 3 (`assistant_conversations`, `assistant_messages`).
- **Migration terakhir:** semua 11 migration (6 Tahap1 + 2 Tahap2 + 2 Tahap3 + `schedule_mahasiswa`) terakhir dijalankan via `php artisan migrate` pada DB nyata.
- **Test:** 105 test Pest (SQLite :memory: + RefreshDatabase), **semua lulus** (254 assertion). `pint`, `npm run lint` bersih/sukses.

---

## Keputusan Teknis Penting (Decision Log)

- **2026-08-09** — Frontend memakai template Metis (Bootstrap Admin Template, puikinsh/Colorlib), diintegrasikan via `laravel-vite-plugin`, bukan dipakai sebagai aset statis terpisah. Aksen warna indigo (`$primary: #6366f1`), sesuai default Metis template.
- **2026-08-09** — Auth memakai Laravel Fortify dengan login `username` (NIM/NIDN), bukan email. RBAC via Laravel Gate.
- **2026-08-09** — Fitur "Analisa Dokumen dengan AI" **dihapus** dari scope, diganti "Asisten Virtual Admin" (FR-05) — chatbot read-only berbasis tool-calling pada data agregat. Asisten tidak pernah dapat akses tulis ke database.
- **2026-08-09** — Prioritas MVP dibagi 3 tahap (ROADMAP.md): Tahap 1 = alur inti; Tahap 2 = operasional; Tahap 3 = fitur lanjutan.
- **2026-08-09** — Register dinonaktifkan (Fortify `features=[]`); user dibuat via seeder. Admin seed: `telo`/`kaspe`.
- **2026-08-09** — Otorisasi dosen **strict**: hanya dosen assign ke schedule dapat lihat submission grup + beri revisi.
- **2026-08-09** — Test pakai SQLite `:memory:` (`phpunit.xml`) + `RefreshDatabase` di `tests/Pest.php`.
- **2026-08-09** — Tahap 2: notifikasi pakai **Alpine.js polling** (bukan Livewire — sesuai keputusan otorisasi), ke endpoint `/notifications/*` tiap 20s.
- **2026-08-09** — Tahap 2: log status pakai **tabel custom `submission_status_logs`** + `SubmissionObserver::updated` (bukan spatie/activitylog).
- **2026-08-09** — Tahap 2: Excel import/export via `maatwebsite/excel ^3.1`; PDF via `barryvdh/laravel-dompdf ^3.1` (pakai dompdf 3.x karena 2.x punya security advisory). Import hanya CSV (simple, cukup untuk kasus jadwal); export rekap tersedia .xlsx maupun .pdf.
- **2026-08-09** — Tahap 2: paket `php artisan serve` dev sudah termasuk `npm run dev` (vite HMR) + `php artisan queue:listen` (via `composer dev`).
- **2026-08-09** — Tahap 3 FR-05: LLM pakai `Illuminate\Support\Facades\Http` (HTTP client bawaan Laravel, tidak perlu Composer dependency tambahan). OpenAI-compatible endpoint dikonfigurasi via `.env` (`ASSISTANT_LLM_URL`, `ASSISTANT_LLM_API_KEY`, `ASSISTANT_LLM_MODEL`). Provider abstraction via `LlmProviderInterface` supaya bisa diganti ke provider lain tanpa ubah service. Tool-call loop max 5 iterasi. Rate limiter didaftarkan di `AssistantServiceProvider::boot()`. Test mock LLM via `mock(LlmProviderInterface::class)` — tidak pernah hit API asli di test.

---

## Log Sesi
*(Append-only. Entri terbaru di paling atas. Format: tanggal — ringkasan — file yang diubah — catatan untuk sesi berikutnya.)*

### 2026-08-11 — Theme Color: Kuning → Indigo (Metis Default)
- **Ringkasan:** Aksen warna diubah dari kuning (`$primary: #F5B400`) ke indigo (`$primary: #6366f1`) sesuai default Metis template. `$warning` diubah ke amber `#f59e0b`, `$info` ke cyan `#06b6d4`, `$dark` ke slate 800 `#1e293b`. `$primary-text-emphasis` diubah dari dark ke white. `btn-primary` text color diubah ke white. Dokumentasi diupdate.
- **File diubah:** `resources/scss/abstracts/_variables.scss`, `resources/scss/app.scss`, `AGENTS.md`, `docs/AGENTS.md`, `docs/FRONTEND-GUIDE.md`, `docs/MEMORY.md`.
- **Keputusan:** Ikuti default palette Metis template. Indigo `#6366f1` sebagai primary. Text on indigo pakai white (`$primary-text-emphasis: #ffffff`). Dark mode overrides otomatis update karena pakai `rgba($primary, ...)`.

### 2026-08-11 — UI/UX Unifikasi Warna Status Badges & Dark Mode Fix
- **Ringkasan:** Unifikasi warna status badges di seluruh view agar konsisten pakai CSS custom properties (`--status-pending-bg/fg`, `--status-resolved-bg/fg`). Chart ApexCharts pakai CSS vars supaya theme-aware. Dark mode badge contrast diperbaiki. `<x-status-badge>` Blade component dibuat untuk single source of truth.
- **File baru:** `resources/views/components/status-badge.blade.php`.
- **File diubah:** `resources/scss/abstracts/_variables.scss` (+CSS vars `:root`), `resources/scss/app.scss` (badge pakai CSS vars), `resources/scss/themes/_dark.scss` (+badge/table/thead overrides), `resources/views/admin/submissions/index.blade.php`, `resources/views/admin/submissions/show.blade.php`, `resources/views/admin/rekap/index.blade.php`, `resources/views/admin/schedules/edit.blade.php`, `resources/views/dosen/submissions/index.blade.php`, `resources/views/dosen/submissions/show.blade.php`, `resources/views/mahasiswa/submissions/show.blade.php`, `resources/views/components/status-history.blade.php`, `resources/views/admin/users/index.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/admin/dashboard.blade.php`.
- **Keputusan:** Badge status pakai CSS vars (`var(--status-pending-bg)`) supaya bisa di-override di dark mode via `[data-bs-theme="dark"]`. Inline hex di login page diganti `bg-primary` class. Chart colors dibaca dari CSS vars via `getComputedStyle()`.
- **Catatan sesi berikutnya:** Semua 105 test lulus (254 assertions), pint lint bersih, build Vite sukses. Dark mode badge sekarang pakai `rgba($warning, 0.2)` background dengan `lighten($warning, 20%)` text. `thead.table-light` di-override di dark mode pakai transparan white.

### 2026-08-10 — Plotting Mahasiswa ke grup sidang + view dosen semua jadwal
- **Ringkasan:** Admin kini bisa plot mahasiswa ke grup sidang (pre-assign, bukan via submission). Pivot table `schedule_mahasiswa` (unique `schedule_id`+`user_id`, cascade delete). Relasi `Schedule::mahasiswas()` (BelongsToMany). FormRequest `StoreScheduleMahasiswaRequest` (validasi role=mahasiswa + unique per jadwal, pesan Bahasa Indonesia). Routes `POST/DELETE /admin/schedules/{schedule}/mahasiswa[/{user}]`. UI: section "Plotting Mahasiswa" di edit jadwal (daftar nama/NIM/judul/status + dropdown tambah + hapus), kolom "Jml Mahasiswa" di index (eager load `withCount`).
- **Dosen side:** `Dosen/SubmissionController@index` hapus filter `where('tanggal_sidang', today())` → tampilkan SEMUA jadwal dosen (sort tanggal ASC). Tab filter Semua/Hari Ini. Mahasiswa ter-plot tanpa submission ditampilkan berstatus "Belum upload" (dari pivot, di-merge dengan submissions tanpa duplikat via `user_id`).
- **Bug laten diperbaiki:** filter tanggal pakai `where('tanggal_sidang', 'Y-m-d')` gagal di SQLite karena cast `date` menyimpan `2026-08-10 00:00:00` — diganti `whereDate()`. Bug ini ada di kode lama (view dosen hanya tampilkan jadwal hari ini), baru ketahuan saat test baru dibuat.
- **File baru:** migration `create_schedule_mahasiswa_table`, `app/Http/Requests/StoreScheduleMahasiswaRequest.php`, `tests/Feature/DosenSubmissionTest.php`.
- **File diubah:** `app/Models/Schedule.php`, `app/Http/Controllers/Admin/ScheduleController.php`, `app/Http/Controllers/Dosen/SubmissionController.php`, `routes/web.php`, `resources/views/admin/schedules/edit.blade.php`, `resources/views/admin/schedules/index.blade.php`, `resources/views/dosen/submissions/index.blade.php`, `tests/Feature/AdminScheduleTest.php`.
- **Catatan:** Route cache sempat bikin route baru tak terlihat — `php artisan route:clear` diperlukan (ada `bootstrap/cache/routes-v7.php`). 105 test lulus, pint + lint bersih. Belum di-commit. Data dev DB dibersihkan dari junk tinker.

### 2026-08-09 — Asisten bebas query semua data (read-only) — queryData + runSqlQuery
- **Ringkasan:** Asisten Virtual kini bisa query SEMUA tabel domain (raw rows) via 2 tool baru: `queryData` (structured JSON → Query Builder, bind params) & `runSqlQuery` (raw SQL SELECT, divalidasi `ReadOnlyGuard`). 4 tool agregat lama dipertahankan. Kolom sensitif & tabel non-domain di-blocklist. System prompt kini memuat deskripsi skema (`SchemaCatalog::schemaDescription()`).
- **File baru:** `SchemaCatalog.php`, `ReadOnlyGuard.php`, `ReadOnlyViolationException.php`, `Tools/QueryDataTool.php`, `Tools/RunSqlQueryTool.php`.
- **File diubah:** `config/assistant.php` (+blok `query`), `.env.example`, `AssistantService.php`, `docs/SCHEMA.md`.
- **Keputusan penting:** Validasi SQL di aplikasi (bukan DB user terpisah) — defense-in-depth via transaksi rollback. Deviasi constraint lama "hanya aggregated results" — kini raw rows diizinkan untuk tabel domain.
- **Catatan sesi berikutnya:** Dokumentasikan di SETUP.md saran DB user `sidang_readonly` (SELECT grant) bila ingin defense-in-depth lebih kuat. Verifikasi manual di `/admin/asisten`.

### 2026-08-09 — Fix kontras chat Asisten Virtual (dark & light mode)
- **Ringkasan:** Bubble balasan Asisten memakai `bg-light`/`bg-white`/`text-primary` yang hardcoded — tidak ikut theme. Di dark mode: kotak putih + teks terang = tidak terbaca; di light mode label "Anda" `text-primary` (kuning #F5B400) kontras rendah. Diganti dengan Bootstrap 5.3 theme-aware utilities yang berubah otomatis via CSS vars (`_variables-dark.scss` sudah di-import di `app.scss`).
- **Perubahan:** `resources/views/admin/assistant/index.blade.php` — bubble Asisten `bg-light`→`bg-body-tertiary`; label role `text-primary`/`text-muted`→`text-body-secondary`; kotak tool `bg-white`→`bg-body-secondary`; `<pre>` tool `bg-light`→`bg-body-tertiary`; bubble "sedang menjawab" `bg-light`→`bg-body-secondary`; footer chat `bg-white`→`bg-body`.
- **Catatan:** Tidak ada perubahan SCSS — utility `bg-body-*` sudah ada di build CSS dan dark vars sudah di-override di `_dark.scss` (`.card-body color: gray-100` tetap kompatibel karena bubble sekarang bg gelap di dark mode). 62 test pass, lint bersih. Belum di-commit.
- **Catatan sesi berikutnya:** Verifikasi manual di browser `/admin/asisten` dark + light mode. Audit global `bg-white`/`bg-light` di view lain: `layouts/auth.blade.php` masih `bg-light` (di luar scope chat, dibiarkan).

### 2026-08-09 — Tahap 3: Dark mode toggle + Dashboard analitik (ApexCharts)
- **Ringkasan:** Port dark mode SCSS dari Metis template (`_dark.scss`, `_light.scss`, token overrides). Alpine.js `themeSwitch` component di header dengan localStorage persistence + system preference fallback. Install `apexcharts` ^6.7.0 via npm, modular entry `resources/js/apex.js`, vendor chunk di `vite.config.js`. Extend `DashboardController@index` dengan 4 aggregate queries. 4 chart ApexCharts di admin dashboard: Status Submission (donut), Submission per Jadwal (bar), Revisi Open/Resolved (donut), Tren Status per Hari (area). Subagent-driven development: 6 tasks, semua approved. 57 test lulus (148 assertions).
- **File baru/diubah:** `resources/scss/themes/_dark.scss`, `resources/scss/themes/_light.scss`, `resources/scss/abstracts/_variables.scss`, `resources/scss/app.scss`, `resources/js/app.js`, `resources/views/layouts/app.blade.php`, `resources/js/apex.js`, `vite.config.js`, `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`, `tests/Feature/AdminDashboardAnalyticsTest.php`, `app/Models/SubmissionStatusLog.php`, `database/factories/SubmissionStatusLogFactory.php`, `package.json`, `package-lock.json`, `ROADMAP.md`, `MEMORY.md`.
- **Catatan perbaikan bug:** `btn-ghost` class tidak didefinisikan — diganti `btn-outline-secondary`. Vite 8 (Rolldown) butuh `manualChunks` sebagai function, bukan object. Reviewer menemukan duplicate query `$schedules` vs `$scheduleSubmissions` — noted untuk cleanup mendatang.
- **Catatan sesi berikutnya:** Sisa Tahap 3: Passkey auth (Fortify WebAuthn). Migration perlu dijalankan di production DB. Git sudah initialized dengan commits.

### 2026-08-09 — Tahap 3 selesai: FR-05 Asisten Virtual Admin (tool-call chatbot read-only)
- **Ringkasan:** Implementasi lengkap FR-05 Asisten Virtual Admin. Migration `assistant_conversations` + `assistant_messages`. Model `AssistantConversation` + `AssistantMessage` (HasFactory, fillable, casts JSON, relationships). Config `config/assistant.php` via `mergeConfigFrom`, semua variabel LLM di `.env` (`ASSISTANT_LLM_URL`, `ASSISTANT_LLM_API_KEY`, `ASSISTANT_LLM_MODEL`, `ASSISTANT_SYSTEM_PROMPT`, `ASSISTANT_RATE_LIMIT_PER_MINUTE`). Service layer: `LlmProviderInterface`, `OpenAiCompatibleProvider` (Http facade), `AssistantService` (tool-call loop max 5 iterasi). 4 tool read-only: `GetStudentProgressTool` (agregat per mahasiswa), `GetDosenWorkloadTool` (COUNT/GROUP BY), `GetStalledRevisionsTool` (poin open > 7 hari), `GetScheduleSummaryTool` (distribusi status). Guardrails: tool whitelist backend, read-only system prompt, rate limiting 10 req/min configurable, 50 message hard limit, `tool_calls` audit JSON column. `AssistantServiceProvider` (singleton binding, rate limiter registration). Controller `Admin\AssistantController` (index/show/chat/conversations/createNew). FormRequest `StoreAssistantMessageRequest`. Routes di `routes/web.php` dengan `role:admin` middleware + `throttle:assistant`. Gate `use-virtual-assistant` di `AppServiceProvider`. Blade view `admin/assistant/index.blade.php` + Alpine.js `chat-assistant.js` (imported in `app.js`). Sidebar menu link. 2 factory (`AssistantConversationFactory`, `AssistantMessageFactory`). 15 test Pest (10 AssistantTest + 5 AssistantToolsTest), 56 total test lulus (143 assertion). Pint + npm run lint bersih.
- **File baru/diubah:** 2 migration, 2 model, `config/assistant.php`, `.env.example`, 2 Service (provider + interface + service), 4 tool classes, 1 provider, 1 controller, 1 FormRequest, `routes/web.php`, `resources/views/admin/assistant/index.blade.php`, `resources/js/components/chat-assistant.js`, `resources/js/app.js`, `resources/views/layouts/app.blade.php`, 2 factory, 2 test files, `MEMORY.md`, `ROADMAP.md`.
- **Catatan perbaikan bug:** `declare(strict_types=1)` menyebabkan route params (string) gagal saat di-pass sebagai `int` ke methods — harus pakai type hint `string`. `GetStalledRevisionsTool` awalnya pakai `NOW()`/`DATEDIFF()` (MySQL-only) — diganti ke `now()->subDays()` + conditional `julianday()` untuk SQLite compatibility. Tool `description()` methods harus punya `return` statement eksplisit (bukan bare expression). FormRequest validation test di Pest: `postJson` + empty string tidak selalu trigger 422 — fix dengan test langsung via Validator facade + `->throws()`.
- **Catatan sesi berikutnya:** FR-05 selesai — Tahap 1, 2, 3 FR-05 semua dicentang di ROADMAP.md. Sisa Tahap 3: Passkey auth, Dark mode, Dashboard analitik. Migration perlu dijalankan di production DB (`php artisan migrate`). Git belum di-init ulang.
- **Ringkasan:** Install `maatwebsite/excel` + `barryvdh/laravel-dompdf`. 2 migration (`submission_status_logs`, `notifications`). Model `SubmissionStatusLog` + `SubmissionObserver` (log otomatis pada perubahan status) didaftarkan di `AppServiceProvider`. `NotificationService` (insert/marking/read-all via DB facade, `data` JSON). 4 FormRequest (`StoreUser/UpdateUser/StoreSchedule/UpdateSchedule` — pesan Bahasa Indonesia). 5 controller admin (`UserController`, `ScheduleController`, `SubmissionController`, `ExportController`, `NotificationController`). Notifikasi dipicu otomatis: dosen buat note → mahasiswa; dosen resolve → mahasiswa; mahasiswa upload lampiran → dosen. Polling Alpine tiap 20s, bell+badge di header layout. Search/filter client-side di tabel admin. Template CSV download. `Submission::statusLogs` relation + history partial di semua halaman show. ROADMAP Tahap 2 dicek semua.
- **File baru/diubah:** composer.json (+2 paket), 2 migration, `app/Models/SubmissionStatusLog.php`, `app/Observers/SubmissionObserver.php`, `app/Services/NotificationService.php`, `app/Providers/AppServiceProvider.php` (register observer), 4 FormRequest, 5 controller admin, `resources/js/app.js` + `resources/views/layouts/app.blade.php` (sidebar+notif), views admin (users/schedules/rekap/pdf) + `components/status-history`, routes, 6 test Pest.
- **Catatan perbaikan bug:** observer pakai `$submission->getOriginal('status')` (bukan `$this->original()` yang error); callback Fortify harus return user.
- **Catatan sesi berikutnya:** Verifikasi manual di browser: login admin, lihat bell notifikasi, buat revision note sebagai dosen lihat notifikasi muncul & badge berubah; cek ikon bootstrap-icons (warning woff); jalankan `php artisan serve` + `npm run dev`. Git belum di-init ulang.

### 2026-08-09 — Tahap 1 selesai: scaffold Laravel + alur inti submission-revisi (test hijau)
- **Ringkasan:** Scaffold Laravel 13.8, Fortify 1.37 (login username), 6 migration, 5 model, gates+policies, FormRequests, controllers per-role, porting Metis frontend (layout, SCSS kuning, Vite). Seeder penuh. 23 test Pest lulus; pint/lint/build sukses. (Lihat log sebelumnya.)

### 2026-08-09 — Setup dokumentasi awal
- (lihat log lama — dokumen .md dibuat.)

---

## Known Issues / Gotchas

- Base `app/Http/Controllers/Controller.php` Laravel 13 kosong — wajib `use AuthorizesRequests, ValidatesRequests;`.
- `Fortify::authenticateUsing()` callback harus **return User**; pakai `Auth::attempt` sendiri gagal (Fortify panggil `guard->login($user)`).
- ESLint v9 butuh flat config `eslint.config.js` (sudah dibuat).
- Sass modern tidak resolve `~bootstrap`/`~bootstrap-icons` — wajib `resolve.alias` di `vite.config.js`.
- Template Metis standalone Vite project — **jangan** `npm install` di root reference; porting ke `resources/` per `FRONTEND-GUIDE.md`.
- Vite warning: `bootstrap-icons.woff/woff2` tidak ter-resolve — ikon font mungkin tak tampil (belum diverifikasi).
- `.git` dihapus saat scaffold — init ulang bila perlu; `_reference/metis-template/` tetap gitignored.
- `storage/app` harus writable (`chmod -R 775 storage`) karena file submission/attachment di `FILESYSTEM_DISK=local`.
- `maatwebsite/excel` import CSV butuh heading row pertama = kolom (`nama_grup_sidang, ruangan, tanggal_sidang, jam_mulai, jam_selesai, dosen_ids`); tanggal format `Y-m-d`, jam `H:i`.

---

## Pertanyaan Terbuka untuk User

- ~~Provider LLM untuk Asisten Virtual (FR-05, Tahap 3)?~~ **Dijawab:** OpenAI-compatible via `Illuminate\Support\Facades\Http`, dikonfigurasi di `.env`.
- Verifikasi manual ikon bootstrap-icons (warning woff di build) — perlu ditindaklanjuti.
- Git init ulang diinginkan?
