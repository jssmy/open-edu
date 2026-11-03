<?php

use App\Modules\Identity\Domain\Role;
use Illuminate\Support\Facades\RateLimiter;

it('inicia y cierra sesión', function () {
    $user = userWithRole(Role::Docente);

    $this->post('/login', ['email' => $user->email, 'password' => 'password-123'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});

it('rechaza credenciales inválidas con un mensaje genérico', function () {
    $user = userWithRole(Role::Docente);

    $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rechaza a un usuario inactivo', function () {
    $user = userWithRole(Role::Docente, ['is_active' => false]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password-123'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('limita los intentos de inicio de sesión (429)', function () {
    RateLimiter::clear('x@test.com|127.0.0.1');

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'x@test.com', 'password' => 'mal']);
    }

    $this->post('/login', ['email' => 'x@test.com', 'password' => 'mal'])->assertStatus(429);
});

it('un estudiante inicia en sus cursos', function () {
    $user = userWithRole(Role::Estudiante);

    $this->post('/login', ['email' => $user->email, 'password' => 'password-123'])->assertRedirect('/my/courses');
});

it('un usuario desactivado con sesión abierta es expulsado', function () {
    $user = userWithRole(Role::Docente);
    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->update(['is_active' => false]);

    $this->get('/dashboard')->assertRedirect('/login');
});

it('exige sesión para las rutas protegidas', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
