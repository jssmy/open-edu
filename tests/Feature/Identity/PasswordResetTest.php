<?php

use App\Modules\Identity\Domain\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('envía el enlace sin revelar si la cuenta existe', function () {
    Notification::fake();
    $user = userWithRole(Role::Docente);

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
    $this->post('/forgot-password', ['email' => 'nadie@test.com'])->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('restablece la contraseña con un token válido', function () {
    $user = userWithRole(Role::Docente);
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token, 'email' => $user->email, 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
    ])->assertRedirect('/login');

    $this->post('/login', ['email' => $user->email, 'password' => 'nueva-clave-1'])->assertRedirect();
});

it('rechaza un enlace ya usado', function () {
    $user = userWithRole(Role::Docente);
    $token = Password::createToken($user);
    $payload = ['token' => $token, 'email' => $user->email, 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1'];

    $this->post('/reset-password', $payload);
    $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
});

it('rechaza un enlace vencido', function () {
    $user = userWithRole(Role::Docente);
    $token = Password::createToken($user);
    $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

    $this->post('/reset-password', [
        'token' => $token, 'email' => $user->email, 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
    ])->assertSessionHasErrors('email');
});
