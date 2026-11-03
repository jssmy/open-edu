<?php

namespace App\Providers;

use App\Modules\Progress\Domain\ProgressRepository;
use App\Modules\Progress\Infrastructure\EloquentProgressRepository;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Progress con sus adaptadores. */
class ProgressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProgressRepository::class, EloquentProgressRepository::class);
    }
}
