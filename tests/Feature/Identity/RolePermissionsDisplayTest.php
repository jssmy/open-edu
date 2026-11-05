<?php

use App\Modules\Identity\Domain\Role;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->admin = userWithRole(Role::Administrador));

it('envía los permisos con etiquetas amigables en la creación de roles', function () {
    $this->actingAs($this->admin)->get('/roles/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Create')
            ->has('permissions')
            ->where('permissions.0.label', fn ($label) => is_string($label) && ! empty($label))
            ->where('permissions.0.module', fn ($module) => is_string($module) && ! empty($module))
            ->where('permissions.0.name', fn ($name) => is_string($name) && ! empty($name))
        );
});

it('envía el catálogo de permisos legibles en el listado de roles', function () {
    $this->actingAs($this->admin)->get('/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Roles/Index')
            ->has('permissions')
            ->where('permissions.0.label', fn ($label) => is_string($label) && ! empty($label))
            ->where('permissions.0.module', fn ($module) => is_string($module) && ! empty($module))
        );
});
