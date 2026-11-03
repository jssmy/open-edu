<?php

namespace App\Providers;

use App\Modules\Identity\Application\PasswordHasher;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\UserRepository;
use App\Modules\Identity\Infrastructure\BcryptPasswordHasher;
use App\Modules\Identity\Infrastructure\EloquentUserRepository;
use App\Modules\Identity\Infrastructure\SpatieRolePermissionRepository;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos del módulo Identity con sus adaptadores. */
class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(RolePermissionRepository::class, SpatieRolePermissionRepository::class);
        $this->app->bind(PasswordHasher::class, BcryptPasswordHasher::class);
    }
}
