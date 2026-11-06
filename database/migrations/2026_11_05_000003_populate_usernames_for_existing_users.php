<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $usersWithoutUsername = User::query()
            ->whereNull('username')
            ->orderBy('id')
            ->get();

        foreach ($usersWithoutUsername as $user) {
            $base = '';

            // Si tiene email, usar la parte local (ej. "ana.estudiante.demo" o "estudiante01")
            if (! empty($user->email)) {
                $base = Str::before($user->email, '@');
            }

            // Si no tiene email o base vacía, normalizar desde su nombre
            if (empty($base)) {
                $base = Str::slug($user->name, '_');
            }

            // Sanitizar base a alfanumérico y guiones bajos
            $base = strtolower(preg_replace('/[^A-Za-z0-9_]/', '_', $base) ?: 'user');
            $candidate = $base;
            $counter = 1;

            while (User::query()->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = "{$base}_{$counter}";
                $counter++;
            }

            $user->update(['username' => $candidate]);
        }
    }

    public function down(): void
    {
        // No destructivo: mantener los usernames generados
    }
};
