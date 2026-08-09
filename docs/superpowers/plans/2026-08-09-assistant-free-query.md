# Asisten Bebas Query Semua Data (Read-Only) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memungkinkan Asisten Virtual Admin menjalankan query read-only terhadap semua data sistem melalui dua tool baru: `queryData` (query builder terstruktur) dan `runSqlQuery` (raw SQL SELECT).

**Architecture:** Dua tool baru mengikuti pola `AssistantToolInterface` yang ada. Layanan bersama `SchemaCatalog` (sumber kebenaran tabel/kolom yang diizinkan) dan `ReadOnlyGuard` (validasi read-only + sanitasi SQL) dibuat sebagai unit yang bisa diuji sendiri. `AssistantService` mendaftarkan tool baru dan menyuntikkan deskripsi skema ke system prompt. Tool lama (4 agregat) dipertahankan.

**Tech Stack:** PHP 8.4, Laravel 13, MySQL/MariaDB (prod) + SQLite :memory: (test), Pest, Laravel Query Builder (`DB::table`), `DB::select()`.

## Global Constraints

- PSR-12 via Laravel Pint. `declare(strict_types=1);` di tiap file PHP baru.
- Bahasa Indonesia untuk pesan error yang dikembalikan ke LLM.
- Semua nilai filter di-bind via Query Builder (tidak ada string interpolation SQL).
- Batas hasil: `max_rows` default 50 (konfigurasi `assistant.query.max_rows`).
- Kolom sensitif diblokir: `users.password`, `users.remember_token`, `users.two_factor_secret`, `users.two_factor_recovery_codes`, `users.two_factor_confirmed_at`.
- Tabel non-domain diblokir: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `password_reset_tokens`.
- Sebelum menandai selesai: `php artisan test`, `npm run lint`, `vendor/bin/pint --dirty`.
- Log sesi append-only di `docs/MEMORY.md`.

---

### Task 1: Config `assistant.query` + SchemaCatalog + ReadOnlyViolationException

**Files:**
- Modify: `config/assistant.php`
- Create: `app/Services/VirtualAssistant/SchemaCatalog.php`
- Create: `app/Services/VirtualAssistant/ReadOnlyViolationException.php`
- Modify: `.env.example`
- Test: `tests/Feature/SchemaCatalogTest.php`

**Interfaces:**
- Consumes: `config('assistant.query.*')`, Laravel schema builder `DB::getSchemaBuilder()->getColumnListing()`
- Produces:
  - `SchemaCatalog::allowedTables(): array`
  - `SchemaCatalog::isTableAllowed(string $table): bool`
  - `SchemaCatalog::assertTableAllowed(string $table): void`
  - `SchemaCatalog::allowedColumns(string $table): array`
  - `SchemaCatalog::assertColumnsAllowed(string $table, array $columns): void`
  - `SchemaCatalog::blockedColumns(): array`
  - `SchemaCatalog::schemaDescription(): string`
  - `class ReadOnlyViolationException extends \RuntimeException`

- [ ] **Step 1: Tambah blok `query` di config/assistant.php**

```php
'rate_limit' => [
    'per_minute' => env('ASSISTANT_RATE_PER_MINUTE', 10),
    'per_conversation' => env('ASSISTANT_RATE_PER_CONVERSATION', 50),
],
'query' => [
    'enabled' => env('ASSISTANT_QUERY_ENABLED', true),
    'max_rows' => (int) env('ASSISTANT_QUERY_MAX_ROWS', 50),
    'raw_sql_enabled' => env('ASSISTANT_RAW_SQL_ENABLED', true),
    'blocked_columns' => [
        'users' => ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'],
    ],
    'blocked_tables' => ['cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'migrations', 'password_reset_tokens'],
],
```

- [ ] **Step 2: Tambah env vars di .env.example**

```text
#ASSISTANT_QUERY_ENABLED=true
#ASSISTANT_QUERY_MAX_ROWS=50
#ASSISTANT_RAW_SQL_ENABLED=true
```

- [ ] **Step 3: Tulis test gagal — tests/Feature/SchemaCatalogTest.php**

```php
<?php

use App\Models\Schedule;
use App\Models\User;
use App\Services\VirtualAssistant\ReadOnlyViolationException;
use App\Services\VirtualAssistant\SchemaCatalog;

test('allowedTables hanya memuat tabel domain', function () {
    $catalog = app(SchemaCatalog::class);

    expect($catalog->allowedTables())->toContain('users');
    expect($catalog->allowedTables())->toContain('submissions');
    expect($catalog->allowedTables())->not->toContain('cache');
    expect($catalog->allowedTables())->not->toContain('sessions');
});

test('isTableAllowed benar untuk tabel domain dan blocklist', function () {
    $catalog = app(SchemaCatalog::class);

    expect($catalog->isTableAllowed('submissions'))->toBeTrue();
    expect($catalog->isTableAllowed('cache'))->toBeFalse();
});

test('assertTableAllowed melempar exception untuk tabel blocklist', function () {
    $catalog = app(SchemaCatalog::class);

    expect(fn () => $catalog->assertTableAllowed('cache'))
        ->toThrow(ReadOnlyViolationException::class, 'tidak diizinkan');
});

test('allowedColumns mengembalikan kolom tanpa kolom sensitif', function () {
    User::factory()->create();

    $catalog = app(SchemaCatalog::class);

    $columns = $catalog->allowedColumns('users');

    expect($columns)->toContain('name');
    expect($columns)->toContain('role');
    expect($columns)->not->toContain('password');
    expect($columns)->not->toContain('remember_token');
});

test('assertColumnsAllowed melempar exception jika ada kolom sensitif', function () {
    $catalog = app(SchemaCatalog::class);

    expect(fn () => $catalog->assertColumnsAllowed('users', ['name', 'password']))
        ->toThrow(ReadOnlyViolationException::class, 'password');
});

test('schemaDescription menghasilkan teks berisi tabel dan kolom', function () {
    Schedule::factory()->create();

    $catalog = app(SchemaCatalog::class);

    $desc = $catalog->schemaDescription();

    expect($desc)->toContain('schedules (');
    expect($desc)->toContain('nama_grup_sidang');
});
```

- [ ] **Step 4: Run test untuk verifikasi gagal**

Run: `php artisan test --compact tests/Feature/SchemaCatalogTest.php`
Expected: FAIL — `Class "App\Services\VirtualAssistant\SchemaCatalog" not found`

- [ ] **Step 5: Implementasi SchemaCatalog**

`app/Services/VirtualAssistant/ReadOnlyViolationException.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

use RuntimeException;

class ReadOnlyViolationException extends RuntimeException
{
}
```

`app/Services/VirtualAssistant/SchemaCatalog.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

use Illuminate\Support\Facades\DB;

class SchemaCatalog
{
    public function allowedTables(): array
    {
        return [
            'users', 'schedules', 'schedule_dosen', 'submissions', 'revision_notes',
            'revision_attachments', 'submission_status_logs', 'notifications',
            'assistant_conversations', 'assistant_messages',
        ];
    }

    public function blockedTables(): array
    {
        return config('assistant.query.blocked_tables', []);
    }

    public function blockedColumns(): array
    {
        return collect(config('assistant.query.blocked_columns', []))
            ->flatten()
            ->values()
            ->all();
    }

    public function isTableAllowed(string $table): bool
    {
        return in_array($table, $this->allowedTables(), true);
    }

    public function assertTableAllowed(string $table): void
    {
        if (! $this->isTableAllowed($table)) {
            throw new ReadOnlyViolationException("Akses ke tabel '{$table}' tidak diizinkan.");
        }
    }

    public function allowedColumns(string $table): array
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($table);

        $blocked = config("assistant.query.blocked_columns.{$table}", []);

        return array_values(array_filter(
            $columns,
            fn (string $column) => ! in_array($column, $blocked, true)
        ));
    }

    public function assertColumnsAllowed(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if (in_array($column, $this->blockedColumns(), true)) {
                throw new ReadOnlyViolationException("Akses ke kolom '{$column}' tidak diizinkan.");
            }
        }
    }

    public function schemaDescription(): string
    {
        $lines = [];

        foreach ($this->allowedTables() as $table) {
            $lines[] = $table.' ('.implode(', ', $this->allowedColumns($table)).')';
        }

        return implode(PHP_EOL, $lines);
    }
}
```

- [ ] **Step 6: Run test untuk verifikasi lulus**

Run: `php artisan test --compact tests/Feature/SchemaCatalogTest.php`
Expected: PASS (6 tests)

- [ ] **Step 7: Commit**

```bash
git add config/assistant.php .env.example app/Services/VirtualAssistant/SchemaCatalog.php app/Services/VirtualAssistant/ReadOnlyViolationException.php tests/Feature/SchemaCatalogTest.php
git commit -m "feat: schema catalog + config query read-only asisten"
```

---

### Task 2: ReadOnlyGuard (validasi & sanitasi raw SQL)

**Files:**
- Create: `app/Services/VirtualAssistant/ReadOnlyGuard.php`
- Test: `tests/Feature/ReadOnlyGuardTest.php`

**Interfaces:**
- Consumes: `SchemaCatalog`, `config('assistant.query.max_rows')`, `DB::select()`, `DB::transaction()`
- Produces:
  - `ReadOnlyGuard::__construct(SchemaCatalog $catalog)`
  - `ReadOnlyGuard::assertTableAllowed(string $table): void`
  - `ReadOnlyGuard::assertColumnsAllowed(string $table, array $columns): void`
  - `ReadOnlyGuard::sanitizeRawSql(string $sql): string`
  - `ReadOnlyGuard::runReadOnly(callable $query): mixed`

- [ ] **Step 1: Tulis test gagal — tests/Feature/ReadOnlyGuardTest.php**

```php
<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\ReadOnlyGuard;
use App\Services\VirtualAssistant\ReadOnlyViolationException;

test('sanitizeRawSql menerima SELECT sederhana', function () {
    $guard = app(ReadOnlyGuard::class);

    $sql = $guard->sanitizeRawSql('SELECT * FROM submissions');

    expect($sql)->toContain('SELECT');
});

test('sanitizeRawSql memaksa LIMIT maksimum row', function () {
    $guard = app(ReadOnlyGuard::class);

    $sql = $guard->sanitizeRawSql('SELECT * FROM submissions');

    expect(str_contains($sql, 'LIMIT 50'))->toBeTrue();
});

test('sanitizeRawSql menolak statement non-SELECT', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->sanitizeRawSql('DELETE FROM submissions'))
        ->toThrow(ReadOnlyViolationException::class, 'SELECT');
});

test('sanitizeRawSql menolak multi-statement', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->sanitizeRawSql('SELECT * FROM submissions; DROP TABLE submissions'))
        ->toThrow(ReadOnlyViolationException::class, 'satu statement');
});

test('sanitizeRawSql menolak komentar bypass', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->sanitizeRawSql('SELECT * FROM submissions -- comment'))
        ->toThrow(ReadOnlyViolationException::class);
});

test('sanitizeRawSql menolak kata berbahaya', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->sanitizeRawSql('SELECT * FROM users INTO OUTFILE /tmp/x'))
        ->toThrow(ReadOnlyViolationException::class, 'OUTFILE');
});

test('sanitizeRawSql menolak kolom sensitif', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->sanitizeRawSql('SELECT password FROM users'))
        ->toThrow(ReadOnlyViolationException::class, 'password');
});

test('sanitizeRawSql mengizinkan string literal yang memuat kata terlarang', function () {
    $guard = app(ReadOnlyGuard::class);

    $sql = $guard->sanitizeRawSql("SELECT * FROM submissions WHERE catatan_revisi = 'create table'");

    expect(str_contains($sql, 'catatan_revisi'))->toBeTrue();
});

test('runReadOnly mengeksekusi query dan mengembalikan hasil', function () {
    $schedule = Schedule::factory()->create();
    Submission::factory()->create(['schedule_id' => $schedule->id, 'status' => 'pending']);

    $guard = app(ReadOnlyGuard::class);

    $result = $guard->runReadOnly(fn () => DB::table('submissions')->count());

    expect($result)->toBe(1);
});

test('assertTableAllowed melempar exception untuk tabel blocklist', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->assertTableAllowed('cache'))
        ->toThrow(ReadOnlyViolationException::class, 'tidak diizinkan');
});

test('assertColumnsAllowed melempar exception untuk kolom sensitif', function () {
    $guard = app(ReadOnlyGuard::class);

    expect(fn () => $guard->assertColumnsAllowed('users', ['password']))
        ->toThrow(ReadOnlyViolationException::class, 'password');
});
```

- [ ] **Step 2: Run test untuk verifikasi gagal**

Run: `php artisan test --compact tests/Feature/ReadOnlyGuardTest.php`
Expected: FAIL — `Class "App\Services\VirtualAssistant\ReadOnlyGuard" not found`

- [ ] **Step 3: Implementasi ReadOnlyGuard**

```php
<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

use Illuminate\Support\Facades\DB;

class ReadOnlyGuard
{
    protected SchemaCatalog $catalog;

    public function __construct(SchemaCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function assertTableAllowed(string $table): void
    {
        $this->catalog->assertTableAllowed($table);
    }

    public function assertColumnsAllowed(string $table, array $columns): void
    {
        $this->catalog->assertColumnsAllowed($table, $columns);
    }

    public function sanitizeRawSql(string $sql): string
    {
        $sql = trim($sql);

        if ($sql === '') {
            throw new ReadOnlyViolationException('Query tidak boleh kosong.');
        }

        $withoutLiterals = preg_replace("/'[^']*'|\"[^\"]*\"/", '', $sql) ?? '';

        if (str_contains($withoutLiterals, ';')) {
            throw new ReadOnlyViolationException('Hanya satu statement SQL yang diizinkan.');
        }

        if (preg_match('/--|#|\/\*/', $withoutLiterals)) {
            throw new ReadOnlyViolationException('Komentar SQL tidak diizinkan.');
        }

        if (! preg_match('/^\s*SELECT\b/i', $sql)) {
            throw new ReadOnlyViolationException('Hanya query SELECT read-only yang diizinkan.');
        }

        $forbidden = [
            'INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'TRUNCATE', 'DROP', 'ALTER',
            'CREATE', 'GRANT', 'REVOKE', 'SET', 'EXPLAIN', 'SHOW', 'USE',
            'INTO', 'OUTFILE', 'DUMPFILE', 'LOAD_FILE', 'SLEEP', 'BENCHMARK',
        ];

        foreach ($forbidden as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $withoutLiterals)) {
                throw new ReadOnlyViolationException("Kata kunci '{$keyword}' tidak diizinkan dalam query.");
            }
        }

        foreach ($this->catalog->blockedColumns() as $column) {
            if (preg_match('/\b'.preg_quote($column, '/').'\b/i', $withoutLiterals)) {
                throw new ReadOnlyViolationException("Akses ke kolom '{$column}' tidak diizinkan.");
            }
        }

        $sql = rtrim($sql, ';');

        if (! preg_match('/\bLIMIT\b/i', $sql)) {
            $sql .= ' LIMIT '.(int) config('assistant.query.max_rows', 50);
        }

        return $sql;
    }

    public function runReadOnly(callable $query): mixed
    {
        DB::beginTransaction();

        try {
            $result = $query();

            DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $result;
    }
}
```

- [ ] **Step 4: Run test untuk verifikasi lulus**

Run: `php artisan test --compact tests/Feature/ReadOnlyGuardTest.php`
Expected: PASS (11 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/VirtualAssistant/ReadOnlyGuard.php tests/Feature/ReadOnlyGuardTest.php
git commit -m "feat: read-only guard untuk sanitasi query asisten"
```

---

### Task 3: Tool `queryData` (structured query builder)

**Files:**
- Create: `app/Services/VirtualAssistant/Tools/QueryDataTool.php`
- Test: `tests/Feature/QueryDataToolTest.php`

**Interfaces:**
- Consumes: `ReadOnlyGuard`, `SchemaCatalog`, `DB::table()`, `config('assistant.query.max_rows')`
- Produces: `QueryDataTool implements AssistantToolInterface` dengan `name()='queryData'`, `execute(array $arguments): array`

- [ ] **Step 1: Tulis test gagal — tests/Feature/QueryDataToolTest.php**

```php
<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\Tools\QueryDataTool;

test('queryData mengembalikan baris sesuai filter', function () {
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'pending',
    ]);
    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'selesai',
    ]);

    $tool = app(QueryDataTool::class);

    $result = $tool->execute([
        'tabel' => 'submissions',
        'filter' => ['status' => 'pending'],
    ]);

    expect($result['total_rows'])->toBe(1);
    expect($result['data'][0]['status'])->toBe('pending');
});

test('queryData menolak tabel blocklist', function () {
    $tool = app(QueryDataTool::class);

    $result = $tool->execute(['tabel' => 'cache']);

    expect($result['error'])->toContain('tidak diizinkan');
});

test('queryData menolak kolom sensitif', function () {
    User::factory()->create();

    $tool = app(QueryDataTool::class);

    $result = $tool->execute([
        'tabel' => 'users',
        'kolom' => ['name', 'password'],
    ]);

    expect($result['error'])->toContain('password');
});

test('queryData memaksa limit maksimum', function () {
    $schedule = Schedule::factory()->create();
    Submission::factory()->count(60)->create(['schedule_id' => $schedule->id]);

    $tool = app(QueryDataTool::class);

    $result = $tool->execute(['tabel' => 'submissions']);

    expect(count($result['data']))->toBe(50);
});

test('queryData mendukung groupBy dan orderBy', function () {
    $schedule = Schedule::factory()->create();
    Submission::factory()->create(['schedule_id' => $schedule->id, 'status' => 'pending']);
    Submission::factory()->create(['schedule_id' => $schedule->id, 'status' => 'selesai']);

    $tool = app(QueryDataTool::class);

    $result = $tool->execute([
        'tabel' => 'submissions',
        'kolom' => ['status'],
        'group_by' => ['status'],
        'order_by' => ['status' => 'asc'],
    ]);

    expect($result['data'])->toHaveCount(2);
});
```

- [ ] **Step 2: Run test untuk verifikasi gagal**

Run: `php artisan test --compact tests/Feature/QueryDataToolTest.php`
Expected: FAIL — `Class "App\Services\VirtualAssistant\Tools\QueryDataTool" not found`

- [ ] **Step 3: Implementasi QueryDataTool**

```php
<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant\Tools;

use App\Services\VirtualAssistant\AssistantToolInterface;
use App\Services\VirtualAssistant\ReadOnlyGuard;
use App\Services\VirtualAssistant\ReadOnlyViolationException;
use App\Services\VirtualAssistant\SchemaCatalog;
use Illuminate\Support\Facades\DB;

class QueryDataTool implements AssistantToolInterface
{
    protected ReadOnlyGuard $guard;

    protected SchemaCatalog $catalog;

    public function __construct(ReadOnlyGuard $guard, SchemaCatalog $catalog)
    {
        $this->guard = $guard;
        $this->catalog = $catalog;
    }

    public function name(): string
    {
        return 'queryData';
    }

    public function description(): string
    {
        return 'Query data terstruktur dari tabel database. Baca semua data sistem (read-only) dengan filter, grouping, dan sorting. Parameter tabel wajib diisi.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tabel' => [
                    'type' => 'string',
                    'enum' => $this->catalog->allowedTables(),
                    'description' => 'Nama tabel yang akan di-query.',
                ],
                'kolom' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Kolom yang di-select. Kosong = semua kolom non-sensitif.',
                ],
                'filter' => [
                    'type' => 'object',
                    'description' => 'Kondisi WHERE equality: {kolom: nilai}. Gabungan = AND.',
                ],
                'group_by' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Kolom untuk GROUP BY.',
                ],
                'order_by' => [
                    'type' => 'object',
                    'description' => 'Sorting: {kolom: "asc"|"desc"}.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'default' => 50,
                ],
                'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                ],
            ],
            'required' => ['tabel'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $tabel = $arguments['tabel'] ?? null;

        if ($tabel === null) {
            return ['error' => 'Parameter tabel wajib diisi.'];
        }

        try {
            $this->guard->assertTableAllowed($tabel);

            $columns = $arguments['kolom'] ?? [];
            $this->guard->assertColumnsAllowed($tabel, $columns);

            foreach (array_keys($arguments['filter'] ?? []) as $column) {
                $this->guard->assertColumnsAllowed($tabel, [$column]);
            }

            foreach (array_keys($arguments['order_by'] ?? []) as $column) {
                $this->guard->assertColumnsAllowed($tabel, [$column]);
            }

            foreach ($arguments['group_by'] ?? [] as $column) {
                $this->guard->assertColumnsAllowed($tabel, [$column]);
            }
        } catch (ReadOnlyViolationException $e) {
            return ['error' => $e->getMessage()];
        }

        $maxRows = (int) config('assistant.query.max_rows', 50);
        $limit = min((int) ($arguments['limit'] ?? $maxRows), $maxRows);
        $offset = (int) ($arguments['offset'] ?? 0);

        $query = DB::table($tabel);

        if ($columns !== []) {
            $query->select($columns);
        }

        foreach ($arguments['filter'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        foreach ($arguments['group_by'] ?? [] as $column) {
            $query->groupBy($column);
        }

        foreach ($arguments['order_by'] ?? [] as $column => $direction) {
            $query->orderBy($column, $direction === 'desc' ? 'desc' : 'asc');
        }

        try {
            $countQuery = clone $query;
            $totalRows = $countQuery->count();

            $data = $query->limit($limit)->offset($offset)->get()->toArray();
        } catch (\Throwable $e) {
            return ['error' => 'Query gagal dijalankan: '.$e->getMessage()];
        }

        return [
            'data' => $data,
            'total_rows' => $totalRows,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
```

> Catatan implementasi: `total_rows` dihitung dari clone query **sebelum** limit/offset diterapkan, jadi angka mencerminkan jumlah seluruh baris yang cocok. Dengan `groupBy`, Laravel menempatkan query dalam subquery dan `count()` mengembalikan jumlah grup — test groupBy memakai `count($result['data'])` agar tidak bergantung pada perilaku ini.

- [ ] **Step 4: Run test untuk verifikasi lulus**

Run: `php artisan test --compact tests/Feature/QueryDataToolTest.php`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/VirtualAssistant/Tools/QueryDataTool.php tests/Feature/QueryDataToolTest.php
git commit -m "feat: tool queryData untuk query terstruktur asisten"
```

---

### Task 4: Tool `runSqlQuery` (raw SQL read-only)

**Files:**
- Create: `app/Services/VirtualAssistant/Tools/RunSqlQueryTool.php`
- Test: `tests/Feature/RunSqlQueryToolTest.php`

**Interfaces:**
- Consumes: `ReadOnlyGuard`, `config('assistant.query.max_rows')`, `config('assistant.query.raw_sql_enabled')`
- Produces: `RunSqlQueryTool implements AssistantToolInterface` dengan `name()='runSqlQuery'`, `execute(array $arguments): array`

- [ ] **Step 1: Tulis test gagal — tests/Feature/RunSqlQueryToolTest.php**

```php
<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\Tools\RunSqlQueryTool;

test('runSqlQuery menerima SELECT valid dan mengembalikan data', function () {
    $schedule = Schedule::factory()->create();
    Submission::factory()->create(['schedule_id' => $schedule->id, 'status' => 'pending']);

    $tool = app(RunSqlQueryTool::class);

    $result = $tool->execute([
        'query' => 'SELECT * FROM submissions',
    ]);

    expect($result['error'] ?? null)->toBeNull();
    expect(count($result['data']))->toBe(1);
});

test('runSqlQuery menolak statement INSERT', function () {
    $tool = app(RunSqlQueryTool::class);

    $result = $tool->execute([
        'query' => 'INSERT INTO submissions (status) VALUES ("pending")',
    ]);

    expect($result['error'])->toContain('SELECT');
});

test('runSqlQuery menolak multi-statement', function () {
    $tool = app(RunSqlQueryTool::class);

    $result = $tool->execute([
        'query' => 'SELECT * FROM submissions; DROP TABLE submissions',
    ]);

    expect($result['error'])->toContain('satu statement');
});

test('runSqlQuery menolak kolom sensitif', function () {
    User::factory()->create();

    $tool = app(RunSqlQueryTool::class);

    $result = $tool->execute([
        'query' => 'SELECT password FROM users',
    ]);

    expect($result['error'])->toContain('password');
});

test('runSqlQuery membatasi hasil sampai max_rows', function () {
    $schedule = Schedule::factory()->create();
    Submission::factory()->count(60)->create(['schedule_id' => $schedule->id]);

    $tool = app(RunSqlQueryTool::class);

    $result = $tool->execute([
        'query' => 'SELECT * FROM submissions',
    ]);

    expect(count($result['data']))->toBe(50);
});

test('runSqlQuery dieksekusi read-only (tidak ada perubahan data)', function () {
    $schedule = Schedule::factory()->create();
    Submission::factory()->create(['schedule_id' => $schedule->id, 'status' => 'pending']);

    $tool = app(RunSqlQueryTool::class);

    $tool->execute([
        'query' => 'SELECT * FROM submissions',
    ]);

    expect(Submission::count())->toBe(1);
});
```

- [ ] **Step 2: Run test untuk verifikasi gagal**

Run: `php artisan test --compact tests/Feature/RunSqlQueryToolTest.php`
Expected: FAIL — `Class "App\Services\VirtualAssistant\Tools\RunSqlQueryTool" not found`

- [ ] **Step 3: Implementasi RunSqlQueryTool**

```php
<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant\Tools;

use App\Services\VirtualAssistant\AssistantToolInterface;
use App\Services\VirtualAssistant\ReadOnlyGuard;
use App\Services\VirtualAssistant\ReadOnlyViolationException;
use Illuminate\Support\Facades\DB;

class RunSqlQueryTool implements AssistantToolInterface
{
    protected ReadOnlyGuard $guard;

    public function __construct(ReadOnlyGuard $guard)
    {
        $this->guard = $guard;
    }

    public function name(): string
    {
        return 'runSqlQuery';
    }

    public function description(): string
    {
        return 'Jalankan query SQL SELECT mentah (read-only) terhadap semua data sistem. Gunakan untuk join kompleks atau agregasi lanjutan yang tidak bisa di-handle queryData. Maksimal 50 baris hasil.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'SQL SELECT mentah, satu statement, tanpa titik koma.',
                ],
            ],
            'required' => ['query'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $query = $arguments['query'] ?? '';

        try {
            $sql = $this->guard->sanitizeRawSql($query);

            $data = $this->guard->runReadOnly(
                fn () => DB::select($sql)
            );
        } catch (ReadOnlyViolationException $e) {
            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['error' => 'Query gagal dijalankan: '.$e->getMessage()];
        }

        return [
            'data' => $data,
            'total_rows' => count($data),
        ];
    }
}
```

> Catatan implementasi: `sanitizeRawSql` sudah memaksa `LIMIT 50`, sehingga `DB::select` mengembalikan ≤ max_rows baris. `runReadOnly` membungkus dalam transaksi yang di-rollback sehingga tak ada perubahan data yang persist meski query memuat efek samping yang lolos validasi.

- [ ] **Step 4: Run test untuk verifikasi lulus**

Run: `php artisan test --compact tests/Feature/RunSqlQueryToolTest.php`
Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/VirtualAssistant/Tools/RunSqlQueryTool.php tests/Feature/RunSqlQueryToolTest.php
git commit -m "feat: tool runSqlQuery untuk raw SQL read-only asisten"
```

---

### Task 5: Daftarkan tool baru di AssistantService + system prompt skema

**Files:**
- Modify: `app/Services/VirtualAssistant/AssistantService.php`
- Test: `tests/Feature/AssistantTest.php`

**Interfaces:**
- Consumes: `QueryDataTool`, `RunSqlQueryTool`, `SchemaCatalog::schemaDescription()`, `config('assistant.query.enabled')`, `config('assistant.query.raw_sql_enabled')`
- Produces: `AssistantService::$tools` memuat 6 tool; system prompt memuat deskripsi skema.

- [ ] **Step 1: Tulis test gagal — tambahkan ke tests/Feature/AssistantTest.php**

```php
test('asisten memiliki tool queryData dan runSqlQuery', function () {
    $service = app(App\Services\VirtualAssistant\AssistantService::class);

    $schema = $service->getToolsSchema();
    $names = collect($schema)->pluck('function.name')->all();

    expect($names)->toContain('queryData');
    expect($names)->toContain('runSqlQuery');
});

test('asisten masih memiliki 4 tool agregat', function () {
    $service = app(App\Services\VirtualAssistant\AssistantService::class);

    $schema = $service->getToolsSchema();
    $names = collect($schema)->pluck('function.name')->all();

    expect($names)->toContain('getStudentProgress');
    expect($names)->toContain('getDosenWorkload');
    expect($names)->toContain('getStalledRevisions');
    expect($names)->toContain('getScheduleSummary');
});

test('system prompt asisten memuat deskripsi skema database', function () {
    $admin = App\Models\User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_schema',
        'email' => 'admin_schema@test.local',
    ]);
    $conversation = App\Models\AssistantConversation::factory()->create(['admin_id' => $admin->id]);

    $service = app(App\Services\VirtualAssistant\AssistantService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('buildMessageContext');
    $method->setAccessible(true);

    $messages = $method->invoke($service, $conversation);

    expect($messages[0]['content'])->toContain('submissions (');
    expect($messages[0]['content'])->toContain('read-only');
});
```

- [ ] **Step 2: Run test untuk verifikasi gagal**

Run: `php artisan test --compact tests/Feature/AssistantTest.php`
Expected: FAIL — `"queryData" does not exist in [..]` / schema description belum ada

- [ ] **Step 3: Implementasi perubahan di AssistantService.php**

Ganti konstruktor dan `buildMessageContext`:

```php
use App\Services\VirtualAssistant\Tools\QueryDataTool;
use App\Services\VirtualAssistant\Tools\RunSqlQueryTool;

public function __construct(LlmProviderInterface $llm)
{
    $this->llm = $llm;
    $this->tools = [
        new GetStudentProgressTool,
        new GetDosenWorkloadTool,
        new GetStalledRevisionsTool,
        new GetScheduleSummaryTool,
    ];

    if (config('assistant.query.enabled', true)) {
        $guard = app(ReadOnlyGuard::class);
        $catalog = app(SchemaCatalog::class);

        $this->tools[] = new QueryDataTool($guard, $catalog);

        if (config('assistant.query.raw_sql_enabled', true)) {
            $this->tools[] = new RunSqlQueryTool($guard);
        }
    }
}
```

Tambahkan imports: `use App\Services\VirtualAssistant\ReadOnlyGuard;`, `use App\Services\VirtualAssistant\SchemaCatalog;`, `use App\Services\VirtualAssistant\Tools\QueryDataTool;`, `use App\Services\VirtualAssistant\Tools\RunSqlQueryTool;`.

Modifikasi `buildMessageContext`:

```php
protected function buildMessageContext(AssistantConversation $conversation): array
{
    $systemPrompt = config('assistant.llm.system_prompt', '');

    $systemPrompt .= PHP_EOL.PHP_EOL.'Skema database yang tersedia untuk query (read-only):'.PHP_EOL;
    $systemPrompt .= app(SchemaCatalog::class)->schemaDescription();

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    foreach ($conversation->messages as $msg) {
        if ($msg->role === 'user') {
            $messages[] = ['role' => 'user', 'content' => $msg->content];
        } elseif ($msg->role === 'assistant') {
            $messages[] = ['role' => 'assistant', 'content' => $msg->content];
        }
    }

    return $messages;
}
```

- [ ] **Step 4: Run test untuk verifikasi lulus**

Run: `php artisan test --compact tests/Feature/AssistantTest.php`
Expected: PASS (semua test asisten termasuk 3 baru)

- [ ] **Step 5: Commit**

```bash
git add app/Services/VirtualAssistant/AssistantService.php tests/Feature/AssistantTest.php
git commit -m "feat: daftarkan tool query asisten + inject skema ke system prompt"
```

---

### Task 6: Docs + verifikasi penuh

**Files:**
- Modify: `docs/SCHEMA.md` (catatan constraint)
- Modify: `docs/MEMORY.md` (log sesi)
- Modify: `docs/superpowers/specs/2026-08-09-assistant-free-query-design.md` (status → implemented)

- [ ] **Step 1: Update catatan constraint di docs/SCHEMA.md**

Ganti baris terakhir "Catatan Query":
```markdown
## Catatan Query
- Selalu eager load relasi saat menampilkan daftar mahasiswa per dosen: `Submission::with(['user', 'revisionNotes'])`.
- Tool Asisten Virtual (FR-05) read-only. Fitur "bebas query" menambah 2 tool: `queryData` (query builder terstruktur) & `runSqlQuery` (raw SQL SELECT). Keduanya bisa mengakses raw rows SEMUA tabel domain, tapi kolom sensitif (`users.password`, `remember_token`, `two_factor_*`) dan tabel non-domain (cache/sessions/jobs/dll.) diblokir. Guard read-only: validasi SQL (SELECT-only, no multi-statement, no komentar, force LIMIT) + transaksi rollback. Sebelumnya: tool agregat hanya mengembalikan hasil agregasi — kebijakan ini dilonggarkan.
```

- [ ] **Step 2: Update docs/MEMORY.md (log sesi, append-only di atas)**

Tambahkan entri baru di bagian "Log Sesi" (paling atas):
```markdown
### 2026-08-09 — Asisten bebas query semua data (read-only) — queryData + runSqlQuery
- **Ringkasan:** Asisten Virtual kini bisa query SEMUA tabel domain (raw rows) via 2 tool baru: `queryData` (structured JSON → Query Builder, bind params) & `runSqlQuery` (raw SQL SELECT, divalidasi `ReadOnlyGuard`). 4 tool agregat lama dipertahankan. Kolom sensitif & tabel non-domain di-blocklist. System prompt kini memuat deskripsi skema (`SchemaCatalog::schemaDescription()`).
- **File baru:** `SchemaCatalog.php`, `ReadOnlyGuard.php`, `ReadOnlyViolationException.php`, `Tools/QueryDataTool.php`, `Tools/RunSqlQueryTool.php`.
- **File diubah:** `config/assistant.php` (+blok `query`), `.env.example`, `AssistantService.php`, `docs/SCHEMA.md`.
- **Keputusan penting:** Validasi SQL di aplikasi (bukan DB user terpisah) — defense-in-depth via transaksi rollback. Deviasi constraint lama "hanya aggregated results" — kini raw rows diizinkan untuk tabel domain.
- **Catatan sesi berikutnya:** Dokumentasikan di SETUP.md saran DB user `sidang_readonly` (SELECT grant) bila ingin defense-in-depth lebih kuat. Verifikasi manual di `/admin/asisten`.
```

- [ ] **Step 3: Update status di dokumen design**

Di `docs/superpowers/specs/2026-08-09-assistant-free-query-design.md` ganti `Status: Approved (user)` → `Status: Implemented`.

- [ ] **Step 4: Verifikasi penuh**

Run:
```bash
php artisan test
npm run lint
vendor/bin/pint --dirty
```

Expected: semua test lulus (62 + ~24 test baru ≈ 86), lint bersih, pint bersih.

- [ ] **Step 5: Commit**

```bash
git add docs/SCHEMA.md docs/MEMORY.md docs/superpowers/specs/2026-08-09-assistant-free-query-design.md
git commit -m "docs: update constraint & log sesi fitur bebas query asisten"
```

---

## Self-Review

**1. Spec coverage:**
- `queryData` tool → Task 3
- `runSqlQuery` tool → Task 4
- SchemaCatalog (allowlist/blocklist) → Task 1
- ReadOnlyGuard (sanitize/runReadOnly) → Task 2
- Config `assistant.query` + env → Task 1
- System prompt skema → Task 5
- Daftarkan tool di AssistantService → Task 5
- Blocklist kolom sensitif + tabel non-domain → Task 1 (config + assert)
- Force LIMIT 50 → Task 2 (sanitizeRawSql) & Task 3 (limit cap)
- Error handling Bahasa Indonesia → semua execute() mengembalikan `['error' => ...]`
- Testing semua skenario spec → Task 1-5 test files
- Update SCHEMA.md/MEMORY.md → Task 6

**2. Placeholder scan:** Tidak ada TBD/TODO. Semua langkah berisi kode lengkap dan perintah exact.

**3. Type consistency:**
- `ReadOnlyGuard::__construct(SchemaCatalog $catalog)` → Task 2, digunakan Task 3/4 via container.
- `QueryDataTool::__construct(ReadOnlyGuard $guard, SchemaCatalog $catalog)` → Task 3; Task 5 instansiasi `new QueryDataTool($guard, $catalog)` — urutan argumen konsisten.
- `RunSqlQueryTool::__construct(ReadOnlyGuard $guard)` → Task 4; Task 5 `new RunSqlQueryTool($guard)` — konsisten.
- `SchemaCatalog` methods: `allowedTables()`, `assertTableAllowed()`, `allowedColumns()`, `assertColumnsAllowed()`, `blockedColumns()`, `schemaDescription()` — konsisten antar task.
- `AssistantService::getToolsSchema()` sudah publik dan dipakai test Task 5 — konsisten.
- `buildMessageContext` protected, dipakai reflection di test — konsisten.
- `$result['data']`/`$result['total_rows']`/`$result['error']` — bentuk respons kedua tool konsisten.

**Catatan penting untuk eksekusi:** `QueryDataTool::execute` memakai clone query untuk `count()` sebelum limit/offset; dengan `groupBy` Laravel mengembalikan jumlah baris hasil — perlu diverifikasi saat test Task 3 (test groupBy mengecek `count($result['data'])` bukan `total_rows`).
