<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiDraftRevisionNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => ['required', 'array', 'min:1'],
            'points.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'points.required' => 'Poin revisi wajib diisi.',
            'points.array' => 'Poin revisi harus berupa daftar.',
            'points.min' => 'Minimal satu poin revisi diperlukan.',
            'points.*.required' => 'Setiap poin revisi wajib diisi.',
            'points.*.string' => 'Setiap poin revisi harus berupa teks.',
        ];
    }
}
