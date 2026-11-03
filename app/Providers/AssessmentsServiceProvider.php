<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Assessments con sus adaptadores. */
class AssessmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Los puertos se enlazan aquí a medida que existan (principio YAGNI).
    }
}
