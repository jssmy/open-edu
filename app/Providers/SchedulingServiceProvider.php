<?php

namespace App\Providers;

use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Infrastructure\EloquentScheduleRepository;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;
use App\Modules\Scheduling\Infrastructure\Policies\SchedulePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Scheduling con sus adaptadores. */
class SchedulingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScheduleRepository::class, EloquentScheduleRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(CourseSchedule::class, SchedulePolicy::class);
    }
}

