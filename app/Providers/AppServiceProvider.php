<?php

namespace App\Providers;

use App\Models\RevisionAttachment;
use App\Models\RevisionNote;
use App\Models\Submission;
use App\Observers\SubmissionObserver;
use App\Policies\RevisionAttachmentPolicy;
use App\Policies\RevisionNotePolicy;
use App\Policies\SubmissionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('isAdmin', fn ($user) => $user->role === 'admin');
        Gate::define('isDosen', fn ($user) => $user->role === 'dosen');
        Gate::define('isMahasiswa', fn ($user) => $user->role === 'mahasiswa');

        Gate::define('viewDosenMenu', fn ($user) => $user->role === 'dosen' || $user->role === 'admin');
        Gate::define('viewAdminMenu', fn ($user) => $user->role === 'admin');
        Gate::define('use-virtual-assistant', fn ($user) => $user->role === 'admin');

        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(RevisionNote::class, RevisionNotePolicy::class);
        Gate::policy(RevisionAttachment::class, RevisionAttachmentPolicy::class);

        Submission::observe(SubmissionObserver::class);
    }
}
