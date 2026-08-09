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
