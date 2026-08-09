<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isMahasiswa()) {
            return $submission->user_id === $user->id;
        }

        if ($user->isDosen()) {
            return $submission->schedule
                && $submission->schedule->dosens()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isMahasiswa();
    }

    public function update(User $user, Submission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $submission->user_id === $user->id;
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }
}
