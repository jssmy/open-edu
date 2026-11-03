<?php

use App\Modules\Identity\Domain\Role;

it('redirige al cambio de contraseña mientras sea temporal', function () {
    $user = userWithRole(Role::Docente, ['must_change_password' => true]);

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/password/change');
    $this->actingAs($user)->get('/password/change')->assertOk();
});

it('permite continuar tras cambiar la contraseña', function () {
    $user = userWithRole(Role::Docente, ['must_change_password' => true]);

    $this->actingAs($user)->post('/password/change', [
        'current_password' => 'password-123', 'password' => 'clave-nueva-9', 'password_confirmation' => 'clave-nueva-9',
    ])->assertRedirect('/dashboard');

    expect($user->fresh()->must_change_password)->toBeFalse();
    $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
});

it('no acepta la misma contraseña temporal', function () {
    $user = userWithRole(Role::Docente, ['must_change_password' => true]);

    $this->actingAs($user)->post('/password/change', [
        'current_password' => 'password-123', 'password' => 'password-123', 'password_confirmation' => 'password-123',
    ])->assertSessionHasErrors('password');
});
