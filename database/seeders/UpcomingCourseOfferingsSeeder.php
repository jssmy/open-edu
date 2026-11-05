<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class UpcomingCourseOfferingsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('UpcomingCourseOfferingsSeeder no puede ejecutarse en producción.');

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $teachers = User::role(Role::Docente->value)->where('is_active', true)->orderBy('id')->get();
        if ($teachers->isEmpty()) {
            $this->command?->error('No hay docentes para asignar a las clases programadas.');

            return;
        }

        $classrooms = Classroom::query()->where('is_active', true)->get();

        // Estudiantes que matricularemos en las clases por empezar (por ejemplo los estudiantes demo)
        $students = Student::query()->where('is_active', true)->orderBy('id')->take(10)->get();

        $today = CarbonImmutable::now()->startOfDay();

        // Definición de cursos que están por empezar (fechas futuras escalonadas)
        /** @var list<array{course_name: string, group_code: string, modality: string, starts_in_days: int, duration_weeks: int, max_students: int}> $upcomingBlueprints */
        $upcomingBlueprints = [
            [
                'course_name' => 'Bases de Datos con SQL',
                'group_code' => 'GRP-SQL-PROX',
                'modality' => 'virtual',
                'starts_in_days' => 3, // Inicia en 3 días
                'duration_weeks' => 8,
                'max_students' => 30,
            ],
            [
                'course_name' => 'Análisis de Datos con Python',
                'group_code' => 'GRP-DATA-PROX',
                'modality' => 'hybrid',
                'starts_in_days' => 7, // Inicia en 1 semana
                'duration_weeks' => 10,
                'max_students' => 25,
            ],
            [
                'course_name' => 'Introducción al Aprendizaje Automático',
                'group_code' => 'GRP-ML-PROX',
                'modality' => 'in_person',
                'starts_in_days' => 12, // Inicia en 12 días
                'duration_weeks' => 8,
                'max_students' => 20,
            ],
            [
                'course_name' => 'Desarrollo Web con React',
                'group_code' => 'GRP-REACT-PROX',
                'modality' => 'virtual',
                'starts_in_days' => 15, // Inicia en 15 días
                'duration_weeks' => 8,
                'max_students' => 35,
            ],
            [
                'course_name' => 'Contenedores con Docker',
                'group_code' => 'GRP-DOCKER-PROX',
                'modality' => 'hybrid',
                'starts_in_days' => 21, // Inicia en 3 semanas
                'duration_weeks' => 6,
                'max_students' => 25,
            ],
            [
                'course_name' => 'APIs con Node.js',
                'group_code' => 'GRP-NODE-PROX',
                'modality' => 'virtual',
                'starts_in_days' => 30, // Inicia en 1 mes
                'duration_weeks' => 8,
                'max_students' => 30,
            ],
        ];

        foreach ($upcomingBlueprints as $idx => $blueprint) {
            $course = Course::query()->firstOrCreate(
                ['name' => $blueprint['course_name']],
                [
                    'description' => "Curso especializado en {$blueprint['course_name']}.",
                    'objectives' => "Aprender y aplicar conceptos clave de {$blueprint['course_name']}.",
                    'status' => 'published',
                ]
            );

            // Asegurar que esté publicado
            if ($course->status !== 'published') {
                $course->update(['status' => 'published']);
            }

            $teacher = $teachers[$idx % $teachers->count()];
            $course->teachers()->syncWithoutDetaching([$teacher->id]);

            $startsOn = $today->addDays($blueprint['starts_in_days'])->toDateString();
            $endsOn = $today->addDays($blueprint['starts_in_days'])->addWeeks($blueprint['duration_weeks'])->toDateString();

            /** @var CourseOffering $offering */
            $offering = CourseOffering::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'group_code' => $blueprint['group_code'],
                ],
                [
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'max_students' => $blueprint['max_students'],
                    'modality' => $blueprint['modality'],
                    'status' => 'published',
                ]
            );

            $offering->teachers()->sync([$teacher->id]);

            // Generar sesiones de clase programadas
            $offering->sessions()->delete();
            $classroom = $classrooms->isNotEmpty() ? $classrooms[$idx % $classrooms->count()] : null;

            $firstSessionDate = $today->addDays($blueprint['starts_in_days'])->toDateString();
            $secondSessionDate = $today->addDays($blueprint['starts_in_days'] + 2)->toDateString();

            $offering->sessions()->create([
                'title' => 'Sesión inaugural: Introducción a la materia',
                'session_date' => $firstSessionDate,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
                'teacher_id' => $teacher->id,
                'classroom_id' => $blueprint['modality'] !== 'virtual' ? $classroom?->id : null,
                'meeting_url' => $blueprint['modality'] !== 'in_person' ? 'https://meet.openedu.test/clase-'.strtolower($blueprint['group_code']) : null,
                'status' => 'scheduled',
            ]);

            $offering->sessions()->create([
                'title' => 'Sesión de profundización',
                'session_date' => $secondSessionDate,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
                'teacher_id' => $teacher->id,
                'classroom_id' => $blueprint['modality'] !== 'virtual' ? $classroom?->id : null,
                'meeting_url' => $blueprint['modality'] !== 'in_person' ? 'https://meet.openedu.test/clase-'.strtolower($blueprint['group_code']) : null,
                'status' => 'scheduled',
            ]);

            // Matricular a los estudiantes para que les figure en "Mis clases" (por iniciar / próximas a empezar)
            foreach ($students as $student) {
                Enrollment::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'course_offering_id' => $offering->id,
                        'status' => EnrollmentStatus::Active,
                        'enrolled_at' => $today->subDays(2),
                    ]
                );
            }
        }

        $this->command?->info('Semilla de cursos/clases por empezar ejecutada con éxito.');
    }
}
