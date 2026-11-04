<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::where('email', env('ADMIN_EMAIL', 'admin@openedu.test'))->first();
    $this->admin->update(['must_change_password' => false]);
});

it('permite a un administrador crear un nuevo rol activo con permisos', function () {
    $response = $this->actingAs($this->admin)->post('/roles', [
        'name' => 'Coordinador',
        'description' => 'Coordina cursos y docentes',
        'permissions' => ['courses.view', 'students.view'],
    ]);

    $response->assertRedirect('/roles');
    $this->assertDatabaseHas('roles', [
        'name' => 'Coordinador',
        'description' => 'Coordina cursos y docentes',
        'is_active' => true,
        'is_system' => false,
    ]);

    $created = SpatieRole::findByName('Coordinador', 'web');
    expect($created->permissions->pluck('name')->all())
        ->toEqualCanonicalizing(['courses.view', 'students.view']);
});

it('rechaza la creación de roles con nombre duplicado independientemente de mayúsculas o si está inactivo', function () {
    $this->actingAs($this->admin)->post('/roles', [
        'name' => 'Tutor',
        'permissions' => [],
    ])->assertRedirect('/roles');

    $role = SpatieRole::findByName('Tutor', 'web');
    $role->update(['is_active' => false]);

    $response = $this->actingAs($this->admin)->post('/roles', [
        'name' => 'tutor',
        'permissions' => [],
    ]);

    $response->assertSessionHasErrors(['name']);
});

it('deniega la gestión de roles a usuarios sin permiso', function () {
    $docente = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
    $docente->assignRole('docente');

    $this->actingAs($docente)->get('/roles')->assertForbidden();
    $this->actingAs($docente)->post('/roles', ['name' => 'Hacker', 'permissions' => []])->assertForbidden();
    $this->actingAs($docente)->put('/roles/1/status', ['active' => false])->assertForbidden();
});

it('permite asignar un nuevo rol personalizado a un usuario', function () {
    $this->actingAs($this->admin)->post('/roles', [
        'name' => 'Coordinador',
        'permissions' => ['courses.view'],
    ]);

    $response = $this->actingAs($this->admin)->post('/users', [
        'name' => 'Carlos Coordinador',
        'email' => 'carlos@openedu.test',
        'role' => 'Coordinador',
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/users');
    $createdUser = User::where('email', 'carlos@openedu.test')->firstOrFail();
    expect($createdUser->hasRole('Coordinador'))->toBeTrue();
});

it('inactiva y reactiva un rol con confirmación cuando tiene usuarios', function () {
    $docente = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
    $docente->assignRole('docente');

    $docenteRole = SpatieRole::findByName('docente', 'web');

    // Sin confirmación debe fallar porque tiene usuarios
    $failResponse = $this->actingAs($this->admin)->put("/roles/{$docenteRole->id}/status", [
        'active' => false,
        'confirm' => false,
    ]);
    $failResponse->assertSessionHasErrors(['general']);
    expect($docenteRole->fresh()->is_active)->toBeTrue();

    // Con confirmación debe inactivarse
    $okResponse = $this->actingAs($this->admin)->put("/roles/{$docenteRole->id}/status", [
        'active' => false,
        'confirm' => true,
    ]);
    $okResponse->assertSessionHasNoErrors();
    expect($docenteRole->fresh()->is_active)->toBeFalse();

    // Reactivar no requiere confirmación
    $reactivateResponse = $this->actingAs($this->admin)->put("/roles/{$docenteRole->id}/status", [
        'active' => true,
    ]);
    $reactivateResponse->assertSessionHasNoErrors();
    expect($docenteRole->fresh()->is_active)->toBeTrue();
});

it('impide inactivar el rol Administrador', function () {
    $adminRole = SpatieRole::findByName('administrador', 'web');

    $response = $this->actingAs($this->admin)->put("/roles/{$adminRole->id}/status", [
        'active' => false,
        'confirm' => true,
    ]);

    $response->assertSessionHasErrors(['general']);
    expect($adminRole->fresh()->is_active)->toBeTrue();
});

it('no permite asignar un rol inactivo a un usuario', function () {
    $this->actingAs($this->admin)->post('/roles', [
        'name' => 'Secretario',
        'permissions' => [],
    ]);
    $role = SpatieRole::findByName('Secretario', 'web');
    $role->update(['is_active' => false]);

    $response = $this->actingAs($this->admin)->post('/users', [
        'name' => 'Luis Test',
        'email' => 'luis@openedu.test',
        'role' => 'Secretario',
        'password' => 'Password123!',
    ]);

    $response->assertSessionHasErrors(['role']);
});
