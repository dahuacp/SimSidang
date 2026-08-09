<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRevisionNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_revisi' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi.',
        ];
    }
}
