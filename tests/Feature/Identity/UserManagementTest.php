<?php

use App\Models\User;
use App\Modules\Identity\Domain\Role;

beforeEach(fn () => $this->admin = userWithRole(Role::Administrador));

it('el administrador crea un usuario con contraseña temporal', function () {
    $this->actingAs($this->admin)->post('/users', [
        'name' => 'Ana', 'email' => 'ana@test.com', 'role' => 'docente', 'password' => 'temporal-123',
    ])->assertRedirect('/users');

    $ana = User::where('email', 'ana@test.com')->first();
    expect($ana->must_change_password)->toBeTrue()->and($ana->hasRole('docente'))->toBeTrue();
});

it('rechaza correos duplicados con 422 de validación', function () {
    $this->actingAs($this->admin)->post('/users', [
        'name' => 'Otra', 'email' => $this->admin->email, 'role' => 'docente', 'password' => 'temporal-123',
    ])->assertSessionHasErrors('email');
});

it('edita usuario y rol', function () {
    $user = userWithRole(Role::Docente);

    $this->actingAs($this->admin)->put("/users/{$user->id}", [
        'name' => 'Nuevo', 'email' => $user->email, 'role' => 'gestor_de_cursos',
    ])->assertRedirect('/users');

    expect($user->fresh()->name)->toBe('Nuevo')->and($user->fresh()->hasRole('gestor_de_cursos'))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['event' => 'user.role_changed']);
});

it('activa y desactiva usuarios', function () {
    $user = userWithRole(Role::Docente);

    $this->actingAs($this->admin)->patch("/users/{$user->id}/status", ['is_active' => false])->assertRedirect();
    expect($user->fresh()->is_active)->toBeFalse();
});

it('no permite desactivarse a sí mismo ni dejar el sistema sin administrador', function () {
    $this->actingAs($this->admin)->patch("/users/{$this->admin->id}/status", ['is_active' => false])
        ->assertSessionHasErrors('general');
    expect($this->admin->fresh()->is_active)->toBeTrue();
});

it('responde 403 a roles sin permiso', function (Role $role) {
    $user = userWithRole($role);

    $this->actingAs($user)->get('/users')->assertForbidden();
    $this->actingAs($user)->post('/users', [])->assertForbidden();
    $this->actingAs($user)->patch("/users/{$this->admin->id}/status", ['is_active' => false])->assertForbidden();
})->with([Role::GestorDeCursos, Role::Docente, Role::Estudiante]);

it('responde 404 para un usuario inexistente', function () {
    $this->actingAs($this->admin)->get('/users/99999')->assertNotFound();
});

it('pagina y filtra el listado de usuarios', function () {
    foreach (range(1, 12) as $i) {
        userWithRole(Role::Docente, ['name' => "Docente {$i}", 'email' => "d{$i}@test.com"]);
    }

    $this->actingAs($this->admin)->get('/users')->assertInertia(fn ($page) => $page
        ->has('users.data', 10)
        ->where('users.meta.total', User::count())
        ->where('users.meta.last_page', (int) ceil(User::count() / 10)));

    $this->actingAs($this->admin)->get('/users?q=d12@&role=docente&status=active')->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.email', 'd12@test.com')
        ->where('filters.q', 'd12@'));
});
