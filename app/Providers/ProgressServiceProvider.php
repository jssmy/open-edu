<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Progress con sus adaptadores. */
class ProgressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Los puertos se enlazan aquí a medida que existan (principio YAGNI).
    }
}
