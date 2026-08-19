<?php

namespace App\Http\Requests;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePembimbingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dosen_id' => ['required', 'array', 'min:1', 'max:2'],
            'dosen_id.*' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Submission|null $submission */
            $submission = $this->route('submission');
            if (! $submission) {
                return;
            }

            $mahasiswaProdi = $submission->user->prodi_id;
            $dosenIds = array_values(array_filter($this->input('dosen_id', []), fn ($v) => $v !== null && $v !== ''));

            foreach ($dosenIds as $index => $dosenId) {
                $dosen = User::find($dosenId);
                if (! $dosen) {
                    continue;
                }

                if (! $dosen->isDosen()) {
                    $validator->errors()->add("dosen_id.{$index}", 'Pembimbing harus berperan dosen.');
                }

                if ($dosen->prodi_id !== $mahasiswaProdi) {
                    $validator->errors()->add("dosen_id.{$index}", 'Dosen harus dari program studi yang sama dengan mahasiswa.');
                }
            }

            $uniqueDosenIds = array_unique($dosenIds);
            if (count($uniqueDosenIds) < count($dosenIds)) {
                $validator->errors()->add('dosen_id', 'Dosen pembimbing tidak boleh sama.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'dosen_id.required' => 'Minimal satu dosen pembimbing wajib dipilih.',
            'dosen_id.array' => 'Format dosen pembimbing tidak valid.',
            'dosen_id.min' => 'Minimal satu dosen pembimbing wajib dipilih.',
            'dosen_id.max' => 'Maksimal 2 dosen pembimbing.',
            'dosen_id.*.exists' => 'Dosen tidak ditemukan.',
        ];
    }
}
