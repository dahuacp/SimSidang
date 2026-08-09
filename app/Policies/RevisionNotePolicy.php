<?php

namespace App\Policies;

use App\Models\RevisionNote;
use App\Models\User;

class RevisionNotePolicy
{
    public function view(User $user, RevisionNote $note): bool
    {
        $submission = $note->submission;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDosen()) {
            return $submission->schedule
                && $submission->schedule->dosens()->where('users.id', $user->id)->exists();
        }

        if ($user->isMahasiswa()) {
            return $submission->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isDosen();
    }

    public function reply(User $user, RevisionNote $note): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isMahasiswa() && $note->submission->user_id === $user->id;
    }

    public function resolve(User $user, RevisionNote $note): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDosen() && $note->dosen_id === $user->id;
    }
}
