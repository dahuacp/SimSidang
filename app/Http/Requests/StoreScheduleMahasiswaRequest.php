<?php

namespace App\Http\Requests;

use App\Services\ScheduleConflictService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schedule = $this->route('schedule');
            $service = app(ScheduleConflictService::class);
            $conflicts = $service->findMahasiswaConflicts(
                [$this->integer('user_id')],
                $schedule->tanggal_sidang->toDateString(),
                $schedule->jam_mulai->format('H:i'),
                $schedule->jam_selesai->format('H:i'),
            );

            foreach ($conflicts as $entry) {
                foreach ($service->describeConflict($entry) as $message) {
                    $validator->errors()->add('user_id', $message);
                }
            }
        });
    }
}
