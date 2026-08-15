<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

use Illuminate\Support\Facades\DB;

class SchemaCatalog
{
    public function allowedTables(): array
    {
        return [
            'users', 'prodis', 'schedules', 'schedule_dosen', 'submissions', 'revision_notes',
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
