<?php

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Application\PasswordHasher;
use Illuminate\Support\Facades\Hash;

final class BcryptPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return Hash::make($plain);
    }
}
