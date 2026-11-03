<?php

namespace App\Providers;

use App\Modules\Assessments\Domain\AssessmentRepository;
use App\Modules\Assessments\Infrastructure\EloquentAssessmentRepository;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Policies\AssessmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AssessmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssessmentRepository::class, EloquentAssessmentRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::define('manageAssessment', [AssessmentPolicy::class, 'manageAssessment']);
    }
}
