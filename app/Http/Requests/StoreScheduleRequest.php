<?php

namespace App\Http\Requests;

use App\Services\ScheduleConflictService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_grup_sidang' => ['required', 'string', 'max:255'],
            'ruangan' => ['required', 'string', 'max:255'],
            'tanggal_sidang' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'jenis_sidang_id' => ['required', 'exists:jenis_sidangs,id'],
            'dosens' => ['nullable', 'array'],
            'dosens.*' => ['exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_grup_sidang.required' => 'Nama grup sidang wajib diisi.',
            'ruangan.required' => 'Ruangan wajib diisi.',
            'tanggal_sidang.required' => 'Tanggal sidang wajib diisi.',
            'tanggal_sidang.date' => 'Tanggal sidang tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'jenis_sidang_id.required' => 'Jenis sidang wajib dipilih.',
            'jenis_sidang_id.exists' => 'Jenis sidang tidak valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(ScheduleConflictService::class);
            $conflicts = $service->findDosenConflicts(
                array_map('intval', (array) $this->input('dosens', [])),
                (string) $this->input('tanggal_sidang'),
                (string) $this->input('jam_mulai'),
                (string) $this->input('jam_selesai'),
            );

            foreach ($conflicts as $entry) {
                foreach ($service->describeConflict($entry) as $message) {
                    $validator->errors()->add('dosens', $message);
                }
            }
        });
    }
}
