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
