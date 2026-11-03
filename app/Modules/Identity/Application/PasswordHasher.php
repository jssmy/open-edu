<?php

namespace App\Modules\Identity\Application;

interface PasswordHasher
{
    public function hash(string $plain): string;
}
