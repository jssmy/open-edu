<?php

use App\Models\User;
use App\Modules\Identity\Domain\Role;
use Database\Factories\Modules\Students\Infrastructure\Models\StudentFactory;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = userWithRole(Role::Administrador);
    $this->docente = userWithRole(Role::Docente);
});

it('allows an administrator to reset a student password and forces a password change on next login', function () {
    $studentUser = User::factory()->create([
        'username' => 'estudiante_reset',
        'password' => Hash::make('antigua-clave-123'),
        'must_change_password' => false,
    ]);
    $student = StudentFactory::new()->create([
        'user_id' => $studentUser->id,
        'identifier' => 'ST-RESET-01',
        'birth_date' => '2010-05-10',
    ]);

    $response = $this->actingAs($this->admin)->put("/students/{$student->id}", [
        'name' => 'Estudiante Reseteado',
        'username' => 'estudiante_reset',
        'identifier' => 'ST-RESET-01',
        'birth_date' => '2010-05-10',
        'temporary_password' => 'nueva-clave-segura-9',
    ]);

    $response->assertRedirect("/students/{$student->id}");

    $studentUser->refresh();
    expect(Hash::check('nueva-clave-segura-9', $studentUser->password))->toBeTrue()
        ->and($studentUser->must_change_password)->toBeTrue()
        ->and($studentUser->remember_token)->toBeNull();

    // El alumno inicia sesión con la nueva clave asignada
    $this->post('/logout');
    $this->post('/login', [
        'login' => 'estudiante_reset',
        'password' => 'nueva-clave-segura-9',
    ])->assertRedirect('/password/change');
});

it('keeps existing password when administrator updates student without providing a password', function () {
    $studentUser = User::factory()->create([
        'username' => 'estudiante_no_change',
        'password' => Hash::make('clave-existente-123'),
        'must_change_password' => false,
    ]);
    $student = StudentFactory::new()->create([
        'user_id' => $studentUser->id,
        'identifier' => 'ST-NO-CHANGE',
        'birth_date' => '2010-05-10',
    ]);

    $response = $this->actingAs($this->admin)->put("/students/{$student->id}", [
        'name' => 'Nombre Actualizado',
        'username' => 'estudiante_no_change',
        'identifier' => 'ST-NO-CHANGE',
        'birth_date' => '2010-05-10',
        'temporary_password' => '',
    ]);

    $response->assertRedirect("/students/{$student->id}");

    $studentUser->refresh();
    expect(Hash::check('clave-existente-123', $studentUser->password))->toBeTrue()
        ->and($studentUser->must_change_password)->toBeFalse();
});

it('rejects password reset for student if password length is under 8 characters', function () {
    $studentUser = User::factory()->create();
    $student = StudentFactory::new()->create(['user_id' => $studentUser->id]);

    $response = $this->actingAs($this->admin)->put("/students/{$student->id}", [
        'name' => 'Estudiante',
        'username' => $studentUser->username,
        'identifier' => $student->identifier,
        'birth_date' => '2010-05-10',
        'temporary_password' => 'corta',
    ]);

    $response->assertSessionHasErrors('temporary_password');
});

it('forbids non-admin users from updating student password', function () {
    $studentUser = User::factory()->create();
    $student = StudentFactory::new()->create(['user_id' => $studentUser->id]);

    $response = $this->actingAs($this->docente)->put("/students/{$student->id}", [
        'name' => 'Estudiante',
        'username' => $studentUser->username,
        'identifier' => $student->identifier,
        'birth_date' => '2010-05-10',
        'temporary_password' => 'nueva-clave-segura-9',
    ]);

    $response->assertForbidden();
});
