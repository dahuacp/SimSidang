<?php

namespace App\Policies;

use App\Models\AssessmentTemplate;
use App\Models\User;

class AssessmentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AssessmentTemplate $template): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AssessmentTemplate $template): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AssessmentTemplate $template): bool
    {
        return $user->isAdmin();
    }
}
