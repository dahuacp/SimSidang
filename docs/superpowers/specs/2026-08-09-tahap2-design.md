# Design Spec — SIMSIDANG Tahap 2: Peningkatan Operasional

**Tanggal:** 2026-08-09 · **Status:** Approved · **Stack:** Laravel 13.8 / PHP 8.4 / MySQL 8 · **Test:** Pest + SQLite `:memory:`

## Goals
Admin tools: CRUD user & jadwal, mass schedule import (Excel), in-app notifications (Alpine polling), submission status-history log, server-side export rekap (Excel + PDF), and client-side table search/filter on admin views. Keep scope to Tahap 2 items from ROADMAP.

## Decisions (approved this session)
| Concern | Decision | Alasan |
|---|---|---|
| Notifications | Alpine.js polling (20s) ke endpoint JSON | Sesuai stack existing, tak perlu Livewire |
| Status log | Tabel `submission_status_logs` + Eloquent observer | Ringan, queryable audit trail |
| Excel | `maatwebsite/excel` (^3.1) | Standar Laravel, handling import errors |
| PDF | `barryvdh/laravel-dompdf` (^3.1, dompdf 3.x) | dompdf 2.x punya security advisory; v3 aman |
| Search/filter | Client-side Alpine pada tabel admin | Simpel, stateless |

## Schema (2 new migrations)
- `submission_status_logs`: `id`, `submission_id` FK→cascade, `status_lama` enum(...), `status_baru` enum(...), `diubah_oleh` FK→users (nullable), timestamps.
- `notifications` (standar Laravel): `id` uuid primary, `type`, `notifiable_type/morphs`, `data` json, `read_at` nullable, timestamps. (Framework-standard table, bukan domain table.)

## Components / Architecture
- **Observer:** `SubmissionObserver::updated` — catat log bila `status` berubah. Register di `EventServiceProvider` (atau `AppServiceProvider` boot via `Submission::observe`).
- **Notifications:** Helper `App\Services\NotificationService::send($user, $type, array $data)` memakai Laravel `notify`. Fire pada: (a) dosen buat revision note → notifikasikan mahasiswa; (b) dosen resolve poin → notifikasikan mahasiswa; (c) mahasiswa upload attachment → notifikasikan dosen.
- **Routes:** `admin/users/{...}`, `admin/schedules/{...}`, `admin/schedules/import`, `admin/schedules/template`, `admin/submissions`, `admin/rekap/export-excel`, `admin/rekap/export-pdf`; notifikasi: `notifications` (GET list), `notifications/unread-count` (GET), `notifications/read-all` (POST).
- **Views baru:** `admin/users/{index,create,edit}`, `admin/schedules/{index,create,edit}`, `admin/import`, `admin/submissions/index` (rekap), `admin/rekap` — serta status-history partial reusable di semua halaman `submission/show`.
- **Sidebar:** tambah menu Admin → Users/Schedules/Submissions/Rekap/Export.

## Scope boundary
- FR-05 (asisten virtual), Tahap 3: **not in scope**.
- Two-factor / passkey: tidak diinstall (Fortify `features=[]`).
- Register tetap dinonaktif (seeder saja).
