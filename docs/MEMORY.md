# MEMORY.md — Vault Memory Project SIMSIDANG

> File ini adalah memori lintas-sesi untuk agent (lihat protokol wajib di `AGENTS.md` bagian 6).
> **Baca bagian "Status Saat Ini" dulu setiap mulai sesi baru.** Bagian "Log Sesi" adalah histori append-only (entri terbaru di paling atas).

---

## Status Saat Ini
*(Bagian ini di-overwrite/update tiap sesi — bukan log historis, tapi snapshot kondisi terkini project.)*

- **Tahap MVP aktif:** Tahap 1 (MVP inti) SELESAI + Tahap 2 (Peningkatan Operasional) SELESAI + Tahap 3 FR-05 SELESAI + Dark Mode + Dashboard Analitik SELESAI. ROADMAP.md: semua item Tahap 1, 2 & FR-05 Tahap 3 + Dark Mode + Dashboard Analitik dicentang. Sisa: Passkey auth.
- **Fitur terakhir dikerjakan:** Tahap 3 — Dark mode toggle (port Metis SCSS themes + Alpine.js themeSwitch + localStorage persistence) + Dashboard Analitik (4 chart ApexCharts: Status Submission donut, Submission per Jadwal bar, Revisi Open/Resolved donut, Tren Status per Hari area). Subagent-driven development dengan 6 task, semua approved.
- **Blocker/isu terbuka:** (1) Warning build Vite: font `bootstrap-icons.woff/woff2` tidak ter-resolve. (2) Git initialized dengan initial commit + feature commits. (3) `laravel boost:mcp` belum dikonfigurasi. (4) Vite/Sass deprecation warnings (Bootstrap 5 legacy, Dart Sass 3.0) — non-blocking.
- **Environment:** Laravel 13.8.0, PHP 8.4.23, MariaDB 11.8.6 (MySQL-compatible), DB `sidangapp2`/user `sidang`/pass `sidang` @ 127.0.0.1:3306. `APP_NAME=SISIDANG`, `APP_LOCALE=id`, `FILESYSTEM_DISK=local`. Packages: `maatwebsite/excel` ^3.1, `barryvdh/laravel-dompdf` ^3.1, `apexcharts` ^6.7.0 (npm).
- **Seed:** admin `telo`/`kaspe`, 4 dosen, 6 mahasiswa, 4 schedules, 6 submissions, 2 revision notes. `migrate:fresh` sukses; semua migration termasuk Tahap 3 (`assistant_conversations`, `assistant_messages`).
- **Migration terakhir:** semua 10 migration (6 Tahap1 + 2 Tahap2 + 2 Tahap3) terakhir dijalankan via `migrate:fresh --seed` pada DB nyata.
- **Test:** 57 test Pest (SQLite :memory: + RefreshDatabase), **semua lulus** (148 assertion). `pint`, `npm run lint`, `npm run build` semua bersih/sukses.

---

## Keputusan Teknis Penting (Decision Log)

- **2026-08-09** — Frontend memakai template Metis (Bootstrap Admin Template, puikinsh/Colorlib), diintegrasikan via `laravel-vite-plugin`, bukan dipakai sebagai aset statis terpisah. Aksen warna kuning (`$primary: #F5B400`).
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
