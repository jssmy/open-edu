<?php

it('omits the password recovery link on the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('¿Olvidaste tu contraseña?')
        ->assertDontSee('/forgot-password');
});

it('returns 404 for public password recovery routes', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', ['email' => 'admin@openedu.test'])->assertNotFound();
    $this->get('/reset-password/sample-token')->assertNotFound();
    $this->post('/reset-password', [
        'token' => 'sample-token',
        'email' => 'admin@openedu.test',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertNotFound();
});
