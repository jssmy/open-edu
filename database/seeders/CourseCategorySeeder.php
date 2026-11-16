<?php

namespace Database\Seeders;

use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseCategorySeeder extends Seeder
{
    /** @var array<string, string> */
    public const DEFAULT_CATEGORIES = [
        'Onboarding' => 'docente',
        'Capacitación' => 'docente',
        'Disciplina' => 'estudiante',
        'Expresión' => 'estudiante',
        'Creatividad' => 'estudiante',
        'Lógica' => 'estudiante',
        'Idiomas' => 'estudiante',
        'Digital' => 'estudiante',
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_CATEGORIES as $name => $roleName) {
            CourseCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'role_name' => $roleName,
                    'is_active' => true,
                ],
            );
        }
    }
}
