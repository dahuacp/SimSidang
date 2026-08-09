<?php

namespace App\Policies;

use App\Models\RevisionAttachment;
use App\Models\User;

class RevisionAttachmentPolicy
{
    public function create(User $user): bool
    {
        return $user->isMahasiswa();
    }

    public function view(User $user, RevisionAttachment $attachment): bool
    {
        $note = $attachment->revisionNote;
        $submission = $note->submission;

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
}
