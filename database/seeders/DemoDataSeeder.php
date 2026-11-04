<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder no puede ejecutarse en producción.');

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $teacher = $this->findOrCreateUser(
            'Docente de demostración',
            'docente.demo@openedu.test',
            Role::Docente,
        );

        /** @var list<array{name: string, email: string, identifier: string}> $studentDefinitions */
        $studentDefinitions = [
            [
                'name' => 'Ana Estudiante Demo',
                'email' => 'ana.estudiante.demo@openedu.test',
                'identifier' => 'DEMO-001',
            ],
            [
                'name' => 'Bruno Estudiante Demo',
                'email' => 'bruno.estudiante.demo@openedu.test',
                'identifier' => 'DEMO-002',
            ],
            [
                'name' => 'Carla Estudiante Demo',
                'email' => 'carla.estudiante.demo@openedu.test',
                'identifier' => 'DEMO-003',
            ],
        ];

        $students = [];
        foreach ($studentDefinitions as $definition) {
            $user = $this->findOrCreateUser(
                $definition['name'],
                $definition['email'],
                Role::Estudiante,
            );
            $students[] = Student::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['identifier' => $definition['identifier'], 'is_active' => true],
            );
        }

        /** @var list<array{name: string, description: string, objectives: string, unit: string, lessons: list<string>}> $courseDefinitions */
        $courseDefinitions = [
            [
                'name' => 'Introducción a OpenEdu',
                'description' => 'Recorrido inicial por el aprendizaje digital.',
                'objectives' => 'Comprender los conceptos básicos del aprendizaje en línea.',
                'unit' => 'Fundamentos',
                'lessons' => ['Bienvenida', 'Aprender en línea'],
            ],
            [
                'name' => 'Herramientas de aprendizaje',
                'description' => 'Uso de herramientas para estudiar y colaborar.',
                'objectives' => 'Aplicar herramientas digitales al estudio.',
                'unit' => 'Herramientas esenciales',
                'lessons' => ['Organizar el estudio', 'Colaborar en línea'],
            ],
        ];

        foreach ($courseDefinitions as $courseDefinition) {
            $course = Course::query()->where('name', $courseDefinition['name'])->first()
                ?? Course::factory()->published()->create([
                    'name' => $courseDefinition['name'],
                    'description' => $courseDefinition['description'],
                    'objectives' => $courseDefinition['objectives'],
                ]);
            $course->teachers()->syncWithoutDetaching([$teacher->id]);

            $unit = $course->units()->firstOrCreate(
                ['title' => $courseDefinition['unit']],
                ['position' => 1],
            );

            $lessons = [];
            foreach ($courseDefinition['lessons'] as $position => $title) {
                $lessons[] = $unit->lessons()->firstOrCreate(
                    ['title' => $title],
                    [
                        'position' => $position + 1,
                        'content_type' => 'text',
                        'content' => "Contenido de demostración: {$title}.",
                        'is_archived' => false,
                    ],
                );
            }

            foreach ($students as $student) {
                if (! Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('course_id', $course->id)
                    ->exists()) {
                    Enrollment::factory()
                        ->for($student)
                        ->for($course)
                        ->create([
                            'status' => EnrollmentStatus::Active,
                            'enrolled_at' => now(),
                        ]);
                }
            }

            $firstStudent = $students[0];
            $firstLesson = $lessons[0];
            DB::table('lesson_progress')->insertOrIgnore([
                'student_id' => $firstStudent->id,
                'lesson_id' => $firstLesson->id,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findOrCreateUser(string $name, string $email, Role $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::DEMO_PASSWORD,
                'must_change_password' => false,
                'is_active' => true,
            ],
        );
        $user->assignRole($role->value);

        return $user;
    }
}
