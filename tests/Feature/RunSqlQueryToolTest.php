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
