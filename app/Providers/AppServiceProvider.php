<?php

namespace App\Providers;

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\RevisionAttachment;
use App\Models\RevisionNote;
use App\Models\Submission;
use App\Observers\SubmissionObserver;
use App\Policies\AssessmentFormPolicy;
use App\Policies\AssessmentTemplatePolicy;
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

        Gate::define('viewDosenMenu', fn ($user) => $user->role === 'dosen');
        Gate::define('viewAdminMenu', fn ($user) => $user->role === 'admin');
        Gate::define('use-virtual-assistant', fn ($user) => $user->role === 'admin');

        Gate::define('assess-penilaian', fn ($user, $submission, $tipe) => app(AssessmentFormPolicy::class)->assess($user, $submission, $tipe));

        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(RevisionNote::class, RevisionNotePolicy::class);
        Gate::policy(RevisionAttachment::class, RevisionAttachmentPolicy::class);
        Gate::policy(AssessmentTemplate::class, AssessmentTemplatePolicy::class);
        Gate::policy(AssessmentForm::class, AssessmentFormPolicy::class);

        Submission::observe(SubmissionObserver::class);
    }
}
