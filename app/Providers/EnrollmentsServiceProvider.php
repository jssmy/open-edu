<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Enrollments con sus adaptadores. */
class EnrollmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Los puertos se enlazan aquí a medida que existan (principio YAGNI).
    }
}
