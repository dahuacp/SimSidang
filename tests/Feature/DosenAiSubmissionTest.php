<?php

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\LlmProviderInterface;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

function makeAiAssignedSubmission(): array
{
    $dosen = User::factory()->dosen()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    $schedule = Schedule::factory()->create();
    $schedule->dosens()->attach($dosen->id);
    $submission = Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
    ]);

    return [$dosen, $submission];
}

function stubAiPdfParser(string $text = 'BAB 1 Pendahuluan'): void
{
    $pdf = mock(Document::class);
    $pdf->shouldReceive('getText')->andReturn($text);

    $parser = mock(Parser::class, 'aiPdfParser');
    $parser->shouldReceive('parseFile')->andReturn($pdf);

    app()->instance(Parser::class, $parser);
}

function llmResponse(array $data): array
{
    return [
        'choices' => [[
            'message' => [
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'tool_calls' => null,
            ],
        ]],
    ];
}

function stubAiLlm(array $data, string $name = 'aiLlm'): void
{
    $mock = mock(LlmProviderInterface::class, $name);
    $mock->shouldReceive('chat')->andReturn(llmResponse($data));

    app()->instance(LlmProviderInterface::class, $mock);
}

function defaultAiData(): array
{
    return [
        'summary' => 'Laporan membahas sistem manajemen sidang berbasis web.',
        'suggestedPoints' => [
            'Bab 1: Rumusan masalah belum spesifik.',
            'Bab 3: Metodologi perlu diagram alur data.',
            'Bab 4: Hasil uji coba kurang lengkap.',
            'Bab 5: Kesimpulan belum menjawab rumusan masalah.',
        ],
    ];
}

test('guest tidak dapat mengakses ai-read', function () {
    [$_, $submission] = makeAiAssignedSubmission();

    $this->post(route('dosen.ai-read', $submission))->assertRedirect(route('login'));
});

test('dosen yang tidak ditugaskan tidak dapat ai-read (403)', function () {
    $dosenLain = User::factory()->dosen()->create();
    [$_, $submission] = makeAiAssignedSubmission();

    $this->actingAs($dosenLain)
        ->post(route('dosen.ai-read', $submission))
        ->assertForbidden();
});

test('submission tanpa file mengembalikan 422', function () {
    [$dosen, $submission] = makeAiAssignedSubmission();
    $submission->update(['file_path' => null]);

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Belum ada file laporan yang diunggah.']);
});

test('analisa berhasil, cache markdown dan respons dibuat', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();
    stubAiLlm(defaultAiData());

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary', 'Laporan membahas sistem manajemen sidang berbasis web.')
        ->assertJsonPath('data.suggestedPoints.0', 'Bab 1: Rumusan masalah belum spesifik.')
        ->assertJsonPath('data.cached', false);

    Storage::disk('local')->assertExists('ai-cache/'.$submission->id.'.md');
    Storage::disk('local')->assertExists('ai-cache/'.$submission->id.'_response.json');
});

test('request kedua memakai cache, LLM tidak dipanggil ulang', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();
    stubAiLlm(defaultAiData(), 'aiLlmCache');

    $this->actingAs($dosen)->post(route('dosen.ai-read', $submission))->assertOk();

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertOk()
        ->assertJsonPath('data.cached', true);
});

test('refresh memaksa analisa ulang', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();

    $mock = mock(LlmProviderInterface::class, 'aiLlmRefresh');
    $mock->shouldReceive('chat')->andReturn(
        llmResponse(defaultAiData()),
        llmResponse(['summary' => 'Ringkasan baru.', 'suggestedPoints' => ['Poin baru.']])
    );
    app()->instance(LlmProviderInterface::class, $mock);

    $this->actingAs($dosen)->post(route('dosen.ai-read', $submission))->assertOk();

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read.refresh', $submission))
        ->assertOk()
        ->assertJsonPath('data.summary', 'Ringkasan baru.')
        ->assertJsonPath('data.cached', false);
});

test('cache respons yang kedaluwarsa (>24 jam) memanggil LLM ulang', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();

    $mock = mock(LlmProviderInterface::class, 'aiLlmExpired');
    $mock->shouldReceive('chat')->andReturn(
        llmResponse(defaultAiData()),
        llmResponse(['summary' => 'Segar.', 'suggestedPoints' => ['Poin segar.']])
    );
    app()->instance(LlmProviderInterface::class, $mock);

    $this->actingAs($dosen)->post(route('dosen.ai-read', $submission))->assertOk();

    $cached = json_decode(Storage::disk('local')->get('ai-cache/'.$submission->id.'_response.json'), true);
    $cached['generated_at'] = now()->subHours(25)->toISOString();
    Storage::disk('local')->put('ai-cache/'.$submission->id.'_response.json', json_encode($cached));

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertOk()
        ->assertJsonPath('data.summary', 'Segar.');
});

test('gagal parsing PDF mengembalikan 500', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();

    $parser = mock(Parser::class, 'aiPdfParserFail');
    $parser->shouldReceive('parseFile')->andThrow(new RuntimeException('corrupt'));
    app()->instance(Parser::class, $parser);

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertStatus(500)
        ->assertJson(['success' => false, 'message' => 'Gagal membaca file PDF.']);
});

test('LLM error mengembalikan 503', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();

    $mock = mock(LlmProviderInterface::class, 'aiLlmError');
    $mock->shouldReceive('chat')->andReturn(['error' => true, 'message' => 'server down']);
    app()->instance(LlmProviderInterface::class, $mock);

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertStatus(503)
        ->assertJsonPath('message', 'Layanan AI tidak tersedia: server down');
});

test('LLM response bukan JSON valid mengembalikan 502', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();

    $mock = mock(LlmProviderInterface::class, 'aiLlmBadJson');
    $mock->shouldReceive('chat')->andReturn([
        'choices' => [['message' => ['content' => 'teks biasa', 'tool_calls' => null]]],
    ]);
    app()->instance(LlmProviderInterface::class, $mock);

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertStatus(502);
});

test('halaman detail menampilkan tombol Baca dengan AI', function () {
    [$dosen, $submission] = makeAiAssignedSubmission();

    $this->actingAs($dosen)
        ->get(route('dosen.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Baca dengan AI');
});

test('draft revisi butuh auth (redirect login)', function () {
    [$_, $submission] = makeAiAssignedSubmission();

    $this->post(route('dosen.revision-notes.draft', $submission))
        ->assertRedirect(route('login'));
});

test('dosen non-assign tidak bisa akses draft (403)', function () {
    $dosenLain = User::factory()->dosen()->create();
    [$_, $submission] = makeAiAssignedSubmission();

    $this->actingAs($dosenLain)
        ->post(route('dosen.revision-notes.draft', $submission), ['points' => ['Poin 1']])
        ->assertForbidden();
});

test('draft revisi simpan ke session, halaman create render textarea terisi bernomor', function () {
    [$dosen, $submission] = makeAiAssignedSubmission();
    $points = ['Poin pertama AI', 'Poin kedua AI', 'Poin ketiga AI'];

    $this->actingAs($dosen)
        ->post(route('dosen.revision-notes.draft', $submission), ['points' => $points])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->actingAs($dosen)
        ->get(route('dosen.revision-notes.create', $submission))
        ->assertOk()
        ->assertSee('1. Poin pertama AI')
        ->assertSee('2. Poin kedua AI')
        ->assertSee('3. Poin ketiga AI');
});

test('halaman create tanpa draft textarea kosong', function () {
    [$dosen, $submission] = makeAiAssignedSubmission();

    $this->actingAs($dosen)
        ->get(route('dosen.revision-notes.create', $submission))
        ->assertOk()
        ->assertSee('placeholder="Tuliskan poin revisi untuk mahasiswa..."', false);
});

test('tombol Buat Revisi dari Hasil AI tampil di halaman detail setelah analisa', function () {
    Storage::fake('local');
    Storage::disk('local')->put('submissions/laporan.pdf', 'fake');
    [$dosen, $submission] = makeAiAssignedSubmission();
    stubAiPdfParser();
    stubAiLlm(defaultAiData());

    $this->actingAs($dosen)
        ->post(route('dosen.ai-read', $submission))
        ->assertOk();

    $this->actingAs($dosen)
        ->get(route('dosen.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Buat Revisi dari Hasil AI');
});

test('draft session dihapus setelah halaman create dibuka', function () {
    [$dosen, $submission] = makeAiAssignedSubmission();
    $points = ['Poin AI'];

    $this->actingAs($dosen)
        ->post(route('dosen.revision-notes.draft', $submission), ['points' => $points])
        ->assertOk();

    // First visit - should show draft
    $this->actingAs($dosen)
        ->get(route('dosen.revision-notes.create', $submission))
        ->assertOk()
        ->assertSee('1. Poin AI');

    // Second visit - draft should be cleared (pulled)
    $this->actingAs($dosen)
        ->get(route('dosen.revision-notes.create', $submission))
        ->assertOk()
        ->assertSee('placeholder="Tuliskan poin revisi untuk mahasiswa..."', false);
});
