<?php

use App\Modules\Identity\Domain\Role;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(fn () => $this->admin = userWithRole(Role::Administrador));

it('lista roles y permisos', function () {
    $this->actingAs($this->admin)->get('/roles')->assertOk();
});

it('edita los permisos de un rol', function () {
    $docente = RoleModel::findByName('docente', 'web');

    $this->actingAs($this->admin)->put("/roles/{$docente->id}/permissions", ['permissions' => ['courses.view']])
        ->assertRedirect();

    expect($docente->fresh()->permissions->pluck('name')->all())->toBe(['courses.view']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'role.permissions_updated']);
});

it('no permite modificar los permisos del Administrador ni permisos inexistentes', function () {
    $adminRole = RoleModel::findByName('administrador', 'web');
    $docente = RoleModel::findByName('docente', 'web');

    $this->actingAs($this->admin)->put("/roles/{$adminRole->id}/permissions", ['permissions' => []])
        ->assertSessionHasErrors('general');
    $this->actingAs($this->admin)->put("/roles/{$docente->id}/permissions", ['permissions' => ['inventado']])
        ->assertSessionHasErrors('permissions');
});

it('rechaza a quien no es administrador', function () {
    $this->actingAs(userWithRole(Role::GestorDeCursos))->get('/roles')->assertForbidden();
});
