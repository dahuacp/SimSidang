<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRevisionNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_poin' => ['required', 'in:open,resolved'],
        ];
    }

    public function messages(): array
    {
        return [
            'status_poin.required' => 'Status poin wajib diisi.',
            'status_poin.in' => 'Status poin tidak valid.',
        ];
    }
}
