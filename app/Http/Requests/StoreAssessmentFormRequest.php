<?php

namespace App\Http\Requests;

use App\Models\AssessmentTemplate;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssessmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipe_penilai' => ['required', Rule::in(['dospem', 'penguji'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'skor_per_item' => ['required', 'array', 'min:1'],
            'skor_per_item.*.item' => ['required', 'integer', 'min:0'],
            'skor_per_item.*.skor' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Submission|null $submission */
            $submission = $this->route('submission');
            $template = $submission ? $this->resolveTemplate($submission, $this->input('tipe_penilai')) : null;

            if (! $template) {
                $validator->errors()->add('template', 'Template penilaian untuk program studi dan jenis sidang ini belum tersedia.');
                $validator->errors()->add('template_id', 'Template penilaian belum tersedia.');

                return;
            }

            foreach ($this->input('skor_per_item', []) as $skor) {
                $idx = $skor['item'] ?? null;
                $item = $template->items[$idx] ?? null;

                if ($item === null) {
                    $validator->errors()->add('skor_per_item', "Item penilaian ke-{$idx} tidak dikenal.");

                    continue;
                }

                if (($skor['skor'] ?? 0) > $item['maksimal']) {
                    $validator->errors()->add("skor_per_item.{$idx}.skor", "Skor item \"{$item['name']}\" maksimal {$item['maksimal']}.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipe_penilai.required' => 'Tipe penilai wajib dipilih.',
            'tipe_penilai.in' => 'Tipe penilai tidak valid.',
            'skor_per_item.required' => 'Skor penilaian wajib diisi.',
            'skor_per_item.min' => 'Minimal satu item penilaian harus diisi.',
            'skor_per_item.*.skor.required' => 'Skor setiap item wajib diisi.',
            'skor_per_item.*.skor.numeric' => 'Skor harus berupa angka.',
            'skor_per_item.*.skor.min' => 'Skor tidak boleh negatif.',
        ];
    }

    private function resolveTemplate(Submission $submission, string $tipe): ?AssessmentTemplate
    {
        return AssessmentTemplate::where('prodi_id', $submission->user->prodi_id)
            ->where('jenis_sidang_id', $submission->schedule->jenis_sidang_id)
            ->where('tipe_penilai', $tipe)
            ->first();
    }
}
