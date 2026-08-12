<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleMahasiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::exists('users', 'id')->where('role', 'mahasiswa'),
                Rule::unique('schedule_mahasiswa', 'user_id')->where('schedule_id', $this->route('schedule')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Mahasiswa wajib dipilih.',
            'user_id.exists' => 'Mahasiswa yang dipilih tidak valid.',
            'user_id.unique' => 'Mahasiswa sudah ter-plot ke grup sidang ini.',
        ];
    }
}
