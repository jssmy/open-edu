<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create([
        'email' => 'docente.test@openedu.test',
        'password' => Hash::make('Secret123!'),
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $this->user->assignRole('docente');
});

it('permite iniciar sesión si el rol del usuario está activo', function () {
    $response = $this->post('/login', [
        'email' => 'docente.test@openedu.test',
        'password' => 'Secret123!',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($this->user);
});

it('bloquea el inicio de sesión con mensaje genérico si el rol del usuario está inactivo', function () {
    $role = SpatieRole::findByName('docente', 'web');
    $role->update(['is_active' => false]);

    $response = $this->post('/login', [
        'email' => 'docente.test@openedu.test',
        'password' => 'Secret123!',
    ]);

    $response->assertSessionHasErrors(['email' => 'Las credenciales no son válidas.']);
    $this->assertGuest();
});

it('permite iniciar sesión nuevamente una vez que el rol es reactivado', function () {
    $role = SpatieRole::findByName('docente', 'web');
    $role->update(['is_active' => false]);

    $this->post('/login', [
        'email' => 'docente.test@openedu.test',
        'password' => 'Secret123!',
    ])->assertSessionHasErrors('email');

    $role->update(['is_active' => true]);

    $response = $this->post('/login', [
        'email' => 'docente.test@openedu.test',
        'password' => 'Secret123!',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($this->user);
});

it('no interrumpe sesiones ya abiertas cuando se inactiva el rol', function () {
    $role = SpatieRole::findByName('docente', 'web');

    // Iniciar sesión mientras está activo
    $this->actingAs($this->user);

    // Inactivar rol
    $role->update(['is_active' => false]);

    // La sesión actual debe seguir teniendo acceso a rutas normales
    $response = $this->get('/dashboard');
    $response->assertOk();
    $this->assertAuthenticatedAs($this->user);
});
