<?php

use App\Models\User;
use App\Modules\Identity\Domain\Role;

beforeEach(fn () => $this->manager = userWithRole(Role::GestorDeCursos));

it('registers a student with name, identifier, birth_date, username and password without requiring email or phone', function () {
    $response = $this->actingAs($this->manager)->post('/students', [
        'name' => 'Carlos Estudiante',
        'identifier' => 'DOC-12345678',
        'birth_date' => '2010-06-15',
        'username' => 'carlos_est',
        'temporary_password' => 'temporal-123',
    ]);

    $response->assertRedirect('/students');

    $user = User::query()->where('username', 'carlos_est')->firstOrFail();
    expect($user->name)->toBe('Carlos Estudiante')
        ->and($user->email)->toBeNull()
        ->and($user->hasRole(Role::Estudiante->value))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->is_active)->toBeTrue();

    $this->assertDatabaseHas('students', [
        'user_id' => $user->id,
        'identifier' => 'DOC-12345678',
        'birth_date' => '2010-06-15',
        'phone' => null,
        'is_active' => true,
    ]);
});

it('rejects registration when username or identifier is already taken', function () {
    $this->actingAs($this->manager)->post('/students', [
        'name' => 'Carlos Uno',
        'identifier' => 'DOC-DUPLICATE',
        'birth_date' => '2010-06-15',
        'username' => 'user_duplicate',
        'temporary_password' => 'temporal-123',
    ])->assertRedirect('/students');

    $response = $this->actingAs($this->manager)->post('/students', [
        'name' => 'Carlos Dos',
        'identifier' => 'DOC-DUPLICATE',
        'birth_date' => '2011-01-20',
        'username' => 'user_duplicate',
        'temporary_password' => 'temporal-123',
    ]);

    $response->assertSessionHasErrors(['username', 'identifier']);
});

it('validates birth_date must be in the past', function () {
    $response = $this->actingAs($this->manager)->post('/students', [
        'name' => 'Carlos Futuro',
        'identifier' => 'DOC-FUTURE',
        'birth_date' => now()->addDay()->toDateString(),
        'username' => 'carlos_futuro',
        'temporary_password' => 'temporal-123',
    ]);

    $response->assertSessionHasErrors(['birth_date']);
});
