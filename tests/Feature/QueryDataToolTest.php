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
