<?php

namespace App\Policies;

use App\Models\AssessmentForm;
use App\Models\Submission;
use App\Models\User;

class AssessmentFormPolicy
{
    public function assess(User $user, Submission $submission, string $tipe): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isDosen()) {
            return false;
        }

        if ($tipe === 'dospem') {
            return $user->mahasiswaBimbingan()->where('users.id', $submission->user_id)->exists();
        }

        if ($tipe === 'penguji') {
            return $submission->schedule
                && $submission->schedule->dosens()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDosen();
    }

    public function view(User $user, AssessmentForm $form): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDosen()) {
            if ($user->id === $form->dosen_id) {
                return true;
            }

            if ($form->tipe_penilai === 'dospem') {
                return $user->mahasiswaBimbingan()
                    ->where('users.id', $form->submission->user_id)
                    ->exists();
            }

            return $form->submission->schedule
                && $form->submission->schedule->dosens()->where('users.id', $user->id)->exists();
        }

        if ($user->isMahasiswa()) {
            return $form->submission->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isDosen() || $user->isAdmin();
    }

    public function update(User $user, AssessmentForm $form): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDosen() && $user->id === $form->dosen_id;
    }

    public function delete(User $user, AssessmentForm $form): bool
    {
        return $user->isAdmin();
    }
}
