<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRevisionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keterangan_mahasiswa' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,docx,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Lampiran harus berupa PDF, DOCX, JPEG, atau PNG.',
            'file.max' => 'Ukuran lampiran maksimal 5MB.',
        ];
    }
}
