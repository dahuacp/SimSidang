<?php

use App\Models\AssessmentForm;
use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\QrCodeService;
use Carbon\Carbon;

function scenarioQrForm(): AssessmentForm
{
    $dosen = User::factory()->dosen()->make(['name' => 'Winarti', 'title' => 'S.Kom., M.Kom.']);

    $schedule = new Schedule;
    $schedule->setRelation('jenisSidang', new JenisSidang(['nama' => 'Tugas Akhir']));
    $submission = new Submission;
    $submission->setRelation('schedule', $schedule);

    $form = AssessmentForm::make();
    $form->created_at = Carbon::create(2026, 3, 17, 10);
    $form->setRelation('dosen', $dosen);
    $form->setRelation('submission', $submission);

    return $form;
}

test('teks tanda tangan berisi nama dosen, jenis sidang, dan tanggal pemberian nilai', function () {
    $service = new QrCodeService;

    expect($service->signatureText(scenarioQrForm()))
        ->toContain('Tanda Tangan Elektronik Penilaian Sidang')
        ->toContain('Dosen: Winarti, S.Kom., M.Kom.')
        ->toContain('Jenis Sidang: Tugas Akhir')
        ->toContain('Tanggal: 17 Maret 2026');
});

test('tanda tangan penilaian berupa data URI PNG valid', function () {
    $service = new QrCodeService;
    $dataUri = $service->penilaianSignature(scenarioQrForm());

    $prefix = 'data:image/png;base64,';
    expect(str_starts_with($dataUri, $prefix))->toBeTrue();

    $binary = base64_decode(substr($dataUri, strlen($prefix)), true);

    expect($binary)->not->toBeFalse()
        ->and(strlen($binary))->toBeGreaterThan(500)
        ->and(substr($binary, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});
