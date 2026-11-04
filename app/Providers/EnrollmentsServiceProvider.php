<?php

namespace App\Providers;

use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Infrastructure\EloquentEnrollmentRepository;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Enrollments con sus adaptadores. */
class EnrollmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EnrollmentRepository::class, EloquentEnrollmentRepository::class);
    }
}
