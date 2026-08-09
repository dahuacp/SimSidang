<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\ReadOnlyGuard;
use App\Services\VirtualAssistant\ReadOnlyViolationException;
use Illuminate\Support\Facades\DB;

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
        ->toThrow(ReadOnlyViolationException::class, 'tidak diizinkan');
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
