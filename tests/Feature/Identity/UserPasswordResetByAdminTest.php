<?php

use App\Models\User;
use App\Modules\Identity\Domain\Role;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = userWithRole(Role::Administrador);
    $this->docente = userWithRole(Role::Docente);
});

it('allows an administrator to reset a teacher password and forces a password change on next login', function () {
    $teacher = User::factory()->create([
        'email' => 'docente_reset@openedu.test',
        'password' => Hash::make('antigua-clave-docente'),
        'must_change_password' => false,
    ]);
    $teacher->assignRole(Role::Docente->value);

    $response = $this->actingAs($this->admin)->put("/users/{$teacher->id}", [
        'name' => 'Prof. Modificado',
        'email' => 'docente_reset@openedu.test',
        'role' => Role::Docente->value,
        'password' => 'nueva-clave-docente-9',
    ]);

    $response->assertRedirect('/users');

    $teacher->refresh();
    expect(Hash::check('nueva-clave-docente-9', $teacher->password))->toBeTrue()
        ->and($teacher->must_change_password)->toBeTrue()
        ->and($teacher->remember_token)->toBeNull();

    // El docente inicia sesión con la nueva clave asignada
    $this->post('/logout');
    $this->post('/login', [
        'login' => 'docente_reset@openedu.test',
        'password' => 'nueva-clave-docente-9',
    ])->assertRedirect('/password/change');
});

it('keeps existing teacher password when administrator updates without password field', function () {
    $teacher = User::factory()->create([
        'email' => 'docente_same@openedu.test',
        'password' => Hash::make('clave-docente-original'),
        'must_change_password' => false,
    ]);
    $teacher->assignRole(Role::Docente->value);

    $response = $this->actingAs($this->admin)->put("/users/{$teacher->id}", [
        'name' => 'Prof. Solo Nombre',
        'email' => 'docente_same@openedu.test',
        'role' => Role::Docente->value,
        'password' => '',
    ]);

    $response->assertRedirect('/users');

    $teacher->refresh();
    expect(Hash::check('clave-docente-original', $teacher->password))->toBeTrue()
        ->and($teacher->must_change_password)->toBeFalse();
});

it('rejects password reset for user if password length is under 8 characters', function () {
    $teacher = User::factory()->create();

    $response = $this->actingAs($this->admin)->put("/users/{$teacher->id}", [
        'name' => 'Prof. Error',
        'email' => $teacher->email,
        'role' => Role::Docente->value,
        'password' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});
