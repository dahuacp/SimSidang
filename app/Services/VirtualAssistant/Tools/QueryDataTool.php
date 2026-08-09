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

            $data = $query->limit($limit)->offset($offset)->get()
                ->map(fn ($row) => (array) $row)
                ->values()
                ->toArray();
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
