<?php

use App\Modules\Identity\Domain\Role;

it('un cambio de rol surte efecto en la siguiente petición con sesión abierta', function () {
    $admin = userWithRole(Role::Administrador);
    $user = userWithRole(Role::GestorDeCursos);

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $this->actingAs($admin)->put("/users/{$user->id}", [
        'name' => $user->name, 'email' => $user->email, 'role' => 'estudiante',
    ]);

    $this->actingAs($user->fresh())->get('/users')->assertForbidden();
    $this->get('/dashboard')->assertOk();
});
