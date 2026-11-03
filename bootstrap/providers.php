<?php

use App\Providers\AppServiceProvider;
use App\Providers\AssessmentsServiceProvider;
use App\Providers\CoursesServiceProvider;
use App\Providers\EnrollmentsServiceProvider;
use App\Providers\IdentityServiceProvider;
use App\Providers\ProgressServiceProvider;
use App\Providers\ReportsServiceProvider;
use App\Providers\StudentsServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    CoursesServiceProvider::class,
    StudentsServiceProvider::class,
    EnrollmentsServiceProvider::class,
    AssessmentsServiceProvider::class,
    ProgressServiceProvider::class,
    ReportsServiceProvider::class,
];
