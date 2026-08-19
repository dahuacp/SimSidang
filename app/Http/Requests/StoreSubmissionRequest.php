<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMahasiswa() ?? false;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            if (! $this->user()->schedulesAsPlot()->whereKey($this->input('schedule_id'))->exists()) {
                $validator->errors()->add('schedule_id', 'Anda belum di-plot ke jadwal sidang ini.');
            }
        });
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'judul_laporan' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.required' => 'Grup sidang wajib dipilih.',
            'schedule_id.integer' => 'Grup sidang tidak valid.',
            'schedule_id.exists' => 'Grup sidang tidak ditemukan.',
            'judul_laporan.required' => 'Judul laporan wajib diisi.',
            'judul_laporan.max' => 'Judul laporan maksimal 255 karakter.',
            'file.required' => 'File laporan wajib diunggah.',
            'file.mimes' => 'File laporan harus berupa PDF.',
            'file.max' => 'Ukuran file laporan maksimal 10MB.',
        ];
    }
}
