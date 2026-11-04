<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const ROLES = [
        'administrador' => [
            'users.manage', 'roles.manage', 'courses.manage', 'courses.view', 'content.manage',
            'students.manage', 'students.view', 'enrollments.manage', 'assessments.manage', 'reports.view',
            'scheduling.manage',
        ],
        'gestor_de_cursos' => [
            'courses.manage', 'courses.view', 'content.manage', 'students.manage', 'students.view',
            'assessments.manage', 'reports.view',
        ],
        'docente' => [
            'courses.view', 'content.manage', 'students.view', 'assessments.manage', 'reports.view',
            'scheduling.view-own',
        ],
        'estudiante' => ['study'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role => $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        $email = env('ADMIN_EMAIL', 'admin@openedu.test');
        if (! User::where('email', $email)->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(16);
            $admin = User::create([
                'name' => 'Administrador',
                'email' => $email,
                'password' => Hash::make($password),
                'must_change_password' => true,
            ]);
            $admin->assignRole('administrador');
            $this->command?->info("Administrador inicial: {$email} / {$password} (debe cambiarla al ingresar)");
        }
    }
}
