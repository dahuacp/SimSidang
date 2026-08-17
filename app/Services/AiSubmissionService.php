<?php

namespace App\Services;

use App\Models\Submission;
use App\Services\VirtualAssistant\LlmProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class AiSubmissionService
{
    public function __construct(
        protected LlmProviderInterface $llm,
        protected Parser $pdfParser,
    ) {}

    public function analyze(Submission $submission, bool $forceRefresh = false): array
    {
        $disk = Storage::disk('local');

        if (! $submission->file_path || ! $disk->exists($submission->file_path)) {
            throw new AiReadServiceException('Belum ada file laporan yang diunggah.', 422);
        }

        $markdown = $this->getMarkdown($submission, $forceRefresh);
        $response = $this->getResponse($submission, $markdown, $forceRefresh);

        return [
            'summary' => $response['summary'],
            'suggestedPoints' => $response['suggestedPoints'],
            'cached' => $response['cached'],
            'model' => config('assistant.llm.model'),
        ];
    }

    protected function getMarkdown(Submission $submission, bool $forceRefresh): string
    {
        $disk = Storage::disk('local');
        $path = 'ai-cache/'.$submission->id.'.md';

        if (! $forceRefresh && $disk->exists($path)) {
            return $disk->get($path);
        }

        $markdown = $this->extractMarkdown($submission);
        $disk->put($path, $markdown);

        return $markdown;
    }

    protected function extractMarkdown(Submission $submission): string
    {
        $disk = Storage::disk('local');

        try {
            $pdf = $this->pdfParser->parseFile($disk->path($submission->file_path));
            $text = $pdf->getText() ?? '';

            return $this->textToMarkdown($text);
        } catch (\Throwable $e) {
            report($e);
            throw new AiReadServiceException('Gagal membaca file PDF.', 500);
        }
    }

    protected function getResponse(Submission $submission, string $markdown, bool $forceRefresh): array
    {
        $disk = Storage::disk('local');
        $path = 'ai-cache/'.$submission->id.'_response.json';

        if (! $forceRefresh && $disk->exists($path)) {
            $cached = json_decode($disk->get($path), true);

            if (is_array($cached) && isset($cached['generated_at'])) {
                $generated = Carbon::parse($cached['generated_at']);

                if ($generated->diffInHours(now()) < 24) {
                    return array_merge($cached, ['cached' => true]);
                }
            }
        }

        $response = $this->askLlm($markdown);
        $response['generated_at'] = now()->toISOString();
        $response['model'] = config('assistant.llm.model');
        $response['cached'] = false;

        $disk->put($path, json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response;
    }

    protected function askLlm(string $markdown): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $markdown],
        ];

        $raw = $this->llm->chat($messages);

        if (isset($raw['error']) && $raw['error'] === true) {
            throw new AiReadServiceException('Layanan AI tidak tersedia: '.$raw['message'], 503);
        }

        $content = $raw['choices'][0]['message']['content'] ?? null;

        if (! is_string($content)) {
            throw new AiReadServiceException('Respons AI tidak valid.', 502);
        }

        $parsed = $this->parseJson($content);

        if (! is_array($parsed) || ! isset($parsed['summary'], $parsed['suggestedPoints'])) {
            throw new AiReadServiceException('Respons AI tidak dalam format yang diharapkan.', 502);
        }

        return [
            'summary' => (string) $parsed['summary'],
            'suggestedPoints' => array_map('strval', (array) $parsed['suggestedPoints']),
        ];
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah asisten dosen pembimbing/penguji sidang Tugas Akhir.
Baca laporan mahasiswa berikut (format Markdown) dan berikan insight rekomendasi revisi perbaikan, fokus pada Bab 1, 3, 4, dan 5:

1. Bab 1 (Pendahuluan): kelengkapan latar belakang, rumusan masalah spesifik dan terukur, batasan masalah, tujuan, manfaat, sistematika penulisan.
2. Bab 3 (Metodologi): kejelasan desain penelitian, alat/bahan, prosedur, teknik pengumpulan data, analisis data, validitas dan reliabilitas.
3. Bab 4 (Hasil dan Pembahasan): kelengkapan hasil, visualisasi data, kedalaman analisis, perbandingan dengan teori atau penelitian lain, pembahasan keterbatasan.
4. Bab 5 (Penutup): kesimpulan menjawab SEMUA rumusan masalah, saran spesifik dan actionable.

Output HANYA JSON valid:
{
  "summary": "Ringkasan 2-3 kalimat: topik, metode, temuan kunci",
  "suggestedPoints": ["Bab 1: ...", "Bab 3: ...", "Bab 4: ...", "Bab 5: ..."]
}
PROMPT;
    }

    protected function parseJson(string $content): ?array
    {
        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function textToMarkdown(string $text): string
    {
        $lines = preg_split('/\r?\n/', $text);
        $markdown = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $markdown[] = '';

                continue;
            }

            if (preg_match('/^BAB\s+\d+|\d+\.\d+\.\d+\s|^[IVXLC]+\s+[A-Z]/i', $trimmed)) {
                $markdown[] = '## '.$trimmed;
            } elseif (preg_match('/^[-*•]\s+/', $trimmed)) {
                $markdown[] = $trimmed;
            } else {
                $markdown[] = $trimmed;
            }
        }

        return implode("\n", $markdown);
    }
}
