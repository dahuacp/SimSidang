<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_laporan' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul_laporan.required' => 'Judul laporan wajib diisi.',
            'judul_laporan.max' => 'Judul laporan maksimal 255 karakter.',
            'file.required' => 'File laporan wajib diunggah.',
            'file.mimes' => 'File laporan harus berupa PDF.',
            'file.max' => 'Ukuran file laporan maksimal 10MB.',
        ];
    }
}
