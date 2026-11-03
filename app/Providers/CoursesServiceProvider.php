<?php

namespace App\Providers;

use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Courses\Domain\FileStorage;
use App\Modules\Courses\Infrastructure\EloquentCourseRepository;
use App\Modules\Courses\Infrastructure\LocalFileStorage;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Policies\CoursePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Courses con sus adaptadores. */
class CoursesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CourseRepository::class, EloquentCourseRepository::class);
        $this->app->bind(FileStorage::class, LocalFileStorage::class);
    }

    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
    }
}
