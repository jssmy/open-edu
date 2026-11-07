<?php

namespace App\Providers;

use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Infrastructure\EloquentEnrollmentRepository;
use App\Modules\Enrollments\Infrastructure\Policies\CourseAccessPolicy;
use App\Modules\Students\Domain\StudentRepository;
use App\Modules\Students\Infrastructure\EloquentStudentRepository;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Modules\Students\Infrastructure\Policies\StudentPolicy;
use App\Shared\Audit\AuditLogger;
use App\Shared\Audit\Auditor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Auditor::class, AuditLogger::class);
        $this->app->bind(StudentRepository::class, EloquentStudentRepository::class);
        $this->app->bind(EnrollmentRepository::class, EloquentEnrollmentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(Student::class, StudentPolicy::class);
        Gate::define('accessCourse', [CourseAccessPolicy::class, 'view']);
    }
}
