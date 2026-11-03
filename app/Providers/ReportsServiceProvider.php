<?php

namespace App\Providers;

use App\Modules\Reports\Application\ReportRepository;
use App\Modules\Reports\Infrastructure\EloquentReportRepository;
use App\Modules\Reports\Infrastructure\Policies\ReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportRepository::class, EloquentReportRepository::class);
    }

    public function boot(): void
    {
        Gate::define('viewCourseProgress', [ReportPolicy::class, 'viewCourseProgress']);
        Gate::define('viewMyProgress', [ReportPolicy::class, 'viewMyProgress']);
    }
}
