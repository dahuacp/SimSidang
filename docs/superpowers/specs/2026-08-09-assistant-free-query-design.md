# Design — Asisten Bebas Query Semua Data (Read-Only)

Tanggal: 2026-08-09
Status: Approved (user)

## Ringkasan

Asisten Virtual Admin (FR-05) saat ini hanya punya 4 tool agregat read-only (`getStudentProgress`, `getDosenWorkload`, `getStalledRevisions`, `getScheduleSummary`) yang mengembalikan statistik agregat. Fitur ini menambah kemampuan asisten untuk **bebas melakukan query terhadap semua data sistem** dengan **restriksi read-only**.

Dua tool baru ditambahkan di samping 4 tool yang dipertahankan:

| Tool | Nama | Fungsi |
|---|---|---|
| Query Builder Terstruktur | `queryData` | LLM kirim spec JSON (tabel, kolom, filter, group by, order, limit) → backend bangun query via Query Builder |
| Raw SQL Read-Only | `runSqlQuery` | LLM kirim SQL SELECT mentah → backend validasi & eksekusi read-only |

## Deviasi constraint lama

`SCHEMA.md`/AGENTS.md saat ini menyatakan "LLM hanya dapat aggregated results, bukan raw rows" dan "jangan kirim raw row data ke LLM". Fitur ini **membuka akses raw rows** (semua data sistem) tetapi tetap:
- read-only (tidak ada tulis/hapus)
- blocklist kolom sensitif (`password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`)
- blocklist tabel non-domain (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `password_reset_tokens`)

Catatan ini akan di-update di `SCHEMA.md` & `MEMORY.md` agar konsisten.

## Keputusan dari klarifikasi

1. **Mekanisme:** Keduanya — `queryData` (structured) sebagai default + `runSqlQuery` (raw SQL) opsional.
2. **Penegakan read-only raw SQL:** Validasi SQL di aplikasi (bukan DB user terpisah). Defense-in-depth via transaksi yang di-rollback. Dokumentasi DB user `sidang_readonly` disarankan di SETUP.md (tidak diimplementasikan tanpa izin).
3. **Kolom sensitif:** Blocklist kolom sensitif.
4. **Batas hasil:** Maks 50 row default, bisa dikonfigurasi.
5. **Tool lama:** 4 tool agregat dipertahankan.

## Arsitektur

```
app/Services/VirtualAssistant/
├── AssistantService.php          (register 2 tool baru)
├── AssistantToolInterface.php    (existing)
├── Tools/
│   ├── GetStudentProgressTool.php   (existing, dipertahankan)
│   ├── GetDosenWorkloadTool.php     (existing)
│   ├── GetStalledRevisionsTool.php  (existing)
│   ├── GetScheduleSummaryTool.php   (existing)
│   ├── QueryDataTool.php            (NEW — structured)
│   └── RunSqlQueryTool.php          (NEW — raw SQL)
├── ReadOnlyGuard.php             (NEW — shared validator service)
└── SchemaCatalog.php             (NEW — tabel/kolom allowlist + blocklist)
```

### SchemaCatalog

Sumber kebenaran skema untuk LLM:
- Daftar tabel + kolom yang boleh di-query (dari `INFORMATION_SCHEMA`/schema cache).
- Daftar kolom yang diblokir: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token` (per tabel).
- Daftar tabel yang diblokir: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `password_reset_tokens`.
- Menyediakan deskripsi skema (markdown/teks) untuk disuntikkan ke system prompt.

### ReadOnlyGuard

Layanan bersama untuk kedua tool:
- `assertTableAllowed(string $table)` — tolak tabel non-domain.
- `assertColumnsAllowed(array $columns)` — tolak kolom sensitif.
- `sanitizeRawSql(string $sql)` — parse memastikan SELECT-only (lihat Tool `runSqlQuery`).
- `runReadOnly(callable $query)` — eksekusi query; untuk raw SQL dibungkus transaksi yang di-rollback.

## Tool `queryData` (Structured)

**Parameter schema:**
```json
{
  "type": "object",
  "properties": {
    "tabel": {
      "type": "string",
      "enum": ["schedules","submissions","revision_notes","revision_attachments","users","schedule_dosen","submission_status_logs","assistant_conversations","assistant_messages","notifications"]
    },
    "kolom": { "type": "array", "items": { "type": "string" }, "description": "Kolom yang di-select. Kosong = semua kolom non-sensitif." },
    "filter": { "type": "object", "description": "Kondisi WHERE equality: {kolom: nilai}. Gabungan = AND." },
    "group_by": { "type": "array", "items": { "type": "string" } },
    "order_by": { "type": "object", "description": "Sorting: {kolom: 'asc'|'desc'}." },
    "limit": { "type": "integer", "minimum": 1, "maximum": 50, "default": 50 },
    "offset": { "type": "integer", "default": 0 }
  },
  "required": ["tabel"],
  "additionalProperties": false
}
```

**Backend:**
- Bangun via `DB::table($tabel)` + `where()` + `groupBy()` + `orderBy()` + `limit(50)` (dipaksa).
- Semua nilai filter di-bind sebagai parameter → SQL injection impossible by construction.
- Tolak tabel/kolom di luar allowlist.
- Kembalikan array hasil.

## Tool `runSqlQuery` (Raw SQL)

**Parameter schema:**
```json
{
  "type": "object",
  "properties": {
    "query": { "type": "string", "description": "SQL SELECT mentah, maksimal 1 statement, tanpa titik koma." }
  },
  "required": ["query"],
  "additionalProperties": false
}
```

**ReadOnlyGuard → sanitizeRawSql:**
1. Tolak multi-statement (deteksi `;`).
2. Tolak komentar `--`, `/* */` (anti-bypass).
3. Harus diawali `SELECT` (whitelist kata kunci). Tolak `INSERT/UPDATE/DELETE/REPLACE/TRUNCATE/DROP/ALTER/CREATE/GRANT/REVOKE/SET/EXPLAIN/SHOW/USE` serta kata berbahaya `INTO OUTFILE`, `INTO DUMPFILE`, `LOAD_FILE`, `SLEEP`, `BENCHMARK`.
4. Force `LIMIT 50` bila tidak ada.
5. Cek `assertColumnsAllowed` via regex untuk kolom sensitif di query.
6. Eksekusi via `DB::select()` di dalam transaksi yang di-rollback (jaring pengaman ekstra), hasil di-slice ke 50 baris.

## Konfigurasi

`config/assistant.php`:
```php
'query' => [
    'enabled' => env('ASSISTANT_QUERY_ENABLED', true),
    'max_rows' => env('ASSISTANT_QUERY_MAX_ROWS', 50),
    'raw_sql_enabled' => env('ASSISTANT_RAW_SQL_ENABLED', true),
    'blocked_columns' => [
        'users' => ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token'],
    ],
    'blocked_tables' => ['cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'migrations', 'password_reset_tokens'],
],
```

System prompt diperbarui: instruksikan LLM lebih dulu memakai `queryData`, gunakan `runSqlQuery` hanya bila butuh join kompleks/agregasi lanjutan, dan wajib `LIMIT`.

## Error handling

- Tabel/kolom tidak diizinkan → `['error' => 'Akses ke tabel/kolom ini tidak diizinkan.']` (Bahasa Indonesia).
- SQL invalid / bukan SELECT → `['error' => 'Hanya query SELECT read-only yang diizinkan.']`.
- Query runtime error (syntax, kolom tak ada) → catch `QueryException`, kembalikan pesan bersih, jangan crash.
- Hasil >50 baris → di-slice, LLM diberi tahu jumlah total lewat `total_rows`.

## Testing

`tests/Feature/AssistantToolsTest.php` + `AssistantTest.php`:
- `queryData` mengembalikan baris sesuai filter/limit
- `queryData` menolak tabel di blocklist (`cache`, `sessions`)
- `queryData` menolak kolom sensitif (`password`)
- `queryData` memaksa limit 50
- `runSqlQuery` menerima SELECT valid, mengembalikan data
- `runSqlQuery` menolak `INSERT`, `UPDATE`, multi-statement, komentar bypass, kolom sensitif
- Tool-call loop di `AssistantService` tetap berjalan dengan tool baru (mock LLM minta `queryData`)
- System prompt memuat deskripsi skema (assert config)

## Scope & file

- **Baru:** `ReadOnlyGuard.php`, `SchemaCatalog.php`, `QueryDataTool.php`, `RunSqlQueryTool.php`
- **Diubah:** `AssistantService.php`, `config/assistant.php`, `.env.example`, `docs/SCHEMA.md` (catatan constraint), `docs/MEMORY.md` (log sesi)
- **Tidak berubah:** controller, routes, views, frontend JS
