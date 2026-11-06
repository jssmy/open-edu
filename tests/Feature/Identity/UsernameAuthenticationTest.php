<?php

use App\Models\User;
use App\Modules\Identity\Domain\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('estudiante_login|127.0.0.1');
    RateLimiter::clear('profesor@openedu.test|127.0.0.1');
});

it('authenticates a student using their username and password', function () {
    $user = User::factory()->create([
        'name' => 'Estudiante Login Test',
        'username' => 'estudiante_login',
        'email' => null,
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    $user->assignRole(Role::Estudiante->value);

    $response = $this->post('/login', [
        'login' => 'estudiante_login',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/my/courses');
    $this->assertAuthenticatedAs($user);
});

it('authenticates an existing teacher/admin using their email and password', function () {
    $teacher = User::factory()->create([
        'name' => 'Profesor Test',
        'username' => null,
        'email' => 'profesor@openedu.test',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    $teacher->assignRole(Role::Docente->value);

    $response = $this->post('/login', [
        'login' => 'profesor@openedu.test',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($teacher);
});

it('rejects invalid password when logging in via username', function () {
    User::factory()->create([
        'username' => 'estudiante_login',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'login' => 'estudiante_login',
        'password' => 'wrong_password',
    ]);

    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});

it('throttles repeated failed attempts using username', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/login', [
            'login' => 'estudiante_throttled',
            'password' => 'wrong',
        ]);
    }

    $response = $this->post('/login', [
        'login' => 'estudiante_throttled',
        'password' => 'wrong',
    ]);

    $response->assertStatus(429);
});
