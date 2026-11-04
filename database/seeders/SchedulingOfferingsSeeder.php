<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SchedulingOfferingsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('SchedulingOfferingsSeeder no puede ejecutarse en producción.');

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $classrooms = $this->seedClassrooms();
        $teachers = $this->seedTeachers();
        $courses = $this->seedPublishedCourses();

        $monday = CarbonImmutable::now()->startOfWeek();
        $startsOn = $monday->subWeek()->toDateString();
        $endsOn = $monday->addWeeks(8)->toDateString();

        $offeringBlueprints = [
            [
                'course' => $courses[0],
                'group_code' => 'GRP-01',
                'modality' => 'in_person',
                'max_students' => 25,
                'status' => 'published',
                'teacher' => $teachers[0],
                'sessions' => [
                    [
                        'date' => $monday->toDateString(),
                        'starts_at' => '08:00',
                        'ends_at' => '10:00',
                        'classroom' => $classrooms[0],
                        'meeting_url' => null,
                    ],
                    [
                        'date' => $monday->addDays(2)->toDateString(),
                        'starts_at' => '08:00',
                        'ends_at' => '10:00',
                        'classroom' => $classrooms[0],
                        'meeting_url' => null,
                    ],
                    [
                        'date' => $monday->addDays(4)->toDateString(),
                        'starts_at' => '08:00',
                        'ends_at' => '10:00',
                        'classroom' => $classrooms[0],
                        'meeting_url' => null,
                    ],
                ],
            ],
            [
                'course' => $courses[0],
                'group_code' => 'GRP-02',
                'modality' => 'virtual',
                'max_students' => 35,
                'status' => 'published',
                'teacher' => $teachers[1],
                'sessions' => [
                    [
                        'date' => $monday->addDays(1)->toDateString(),
                        'starts_at' => '18:00',
                        'ends_at' => '20:00',
                        'classroom' => null,
                        'meeting_url' => 'https://meet.openedu.test/python-grp-b',
                    ],
                    [
                        'date' => $monday->addDays(3)->toDateString(),
                        'starts_at' => '18:00',
                        'ends_at' => '20:00',
                        'classroom' => null,
                        'meeting_url' => 'https://meet.openedu.test/python-grp-b',
                    ],
                ],
            ],
            [
                'course' => $courses[1],
                'group_code' => 'GRP-01',
                'modality' => 'hybrid',
                'max_students' => 20,
                'status' => 'published',
                'teacher' => $teachers[2],
                'sessions' => [
                    [
                        'date' => $monday->toDateString(),
                        'starts_at' => '10:00',
                        'ends_at' => '12:00',
                        'classroom' => $classrooms[1],
                        'meeting_url' => 'https://meet.openedu.test/js-hibrido',
                    ],
                    [
                        'date' => $monday->addDays(2)->toDateString(),
                        'starts_at' => '10:00',
                        'ends_at' => '12:00',
                        'classroom' => $classrooms[1],
                        'meeting_url' => 'https://meet.openedu.test/js-hibrido',
                    ],
                ],
            ],
            [
                'course' => $courses[2],
                'group_code' => 'GRP-01',
                'modality' => 'in_person',
                'max_students' => 24,
                'status' => 'published',
                'teacher' => $teachers[3],
                'sessions' => [
                    [
                        'date' => $monday->addDays(1)->toDateString(),
                        'starts_at' => '14:00',
                        'ends_at' => '16:00',
                        'classroom' => $classrooms[2],
                        'meeting_url' => null,
                    ],
                    [
                        'date' => $monday->addDays(3)->toDateString(),
                        'starts_at' => '14:00',
                        'ends_at' => '16:00',
                        'classroom' => $classrooms[2],
                        'meeting_url' => null,
                    ],
                ],
            ],
            [
                'course' => $courses[3],
                'group_code' => 'GRP-01',
                'modality' => 'virtual',
                'max_students' => 30,
                'status' => 'published',
                'teacher' => $teachers[4],
                'sessions' => [
                    [
                        'date' => $monday->addDays(1)->toDateString(),
                        'starts_at' => '09:00',
                        'ends_at' => '11:00',
                        'classroom' => null,
                        'meeting_url' => 'https://meet.openedu.test/sql-intensivo',
                    ],
                    [
                        'date' => $monday->addDays(4)->toDateString(),
                        'starts_at' => '10:00',
                        'ends_at' => '12:00',
                        'classroom' => null,
                        'meeting_url' => 'https://meet.openedu.test/sql-intensivo',
                    ],
                ],
            ],
            [
                'course' => $courses[4],
                'group_code' => 'GRP-01',
                'modality' => 'in_person',
                'max_students' => 18,
                'status' => 'published',
                'teacher' => $teachers[2],
                'sessions' => [
                    [
                        'date' => $monday->addDays(2)->toDateString(),
                        'starts_at' => '15:00',
                        'ends_at' => '17:00',
                        'classroom' => $classrooms[3],
                        'meeting_url' => null,
                    ],
                    [
                        'date' => $monday->addDays(4)->toDateString(),
                        'starts_at' => '15:00',
                        'ends_at' => '17:00',
                        'classroom' => $classrooms[3],
                        'meeting_url' => null,
                    ],
                ],
            ],
            [
                'course' => $courses[5],
                'group_code' => 'GRP-01',
                'modality' => 'hybrid',
                'max_students' => 22,
                'status' => 'published',
                'teacher' => $teachers[4],
                'sessions' => [
                    [
                        'date' => $monday->addDays(5)->toDateString(),
                        'starts_at' => '09:00',
                        'ends_at' => '12:00',
                        'classroom' => $classrooms[1],
                        'meeting_url' => 'https://meet.openedu.test/data-sabatino',
                    ],
                ],
            ],
        ];

        foreach ($offeringBlueprints as $blueprint) {
            /** @var Course $course */
            $course = $blueprint['course'];
            /** @var User $teacher */
            $teacher = $blueprint['teacher'];

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
                    'status' => $blueprint['status'],
                ],
            );

            $offering->teachers()->sync([$teacher->id]);
            $course->teachers()->syncWithoutDetaching([$teacher->id]);

            $offering->sessions()->delete();
            foreach ($blueprint['sessions'] as $session) {
                $offering->sessions()->create([
                    'title' => 'Sesión de clase',
                    'session_date' => $session['date'],
                    'starts_at' => $session['starts_at'],
                    'ends_at' => $session['ends_at'],
                    'teacher_id' => $teacher->id,
                    'classroom_id' => $session['classroom']?->id,
                    'meeting_url' => $session['meeting_url'],
                    'status' => 'scheduled',
                ]);
            }
        }
    }

    /** @return list<Classroom> */
    private function seedClassrooms(): array
    {
        $definitions = [
            ['name' => 'Aula 101 - Pabellón A', 'capacity' => 30],
            ['name' => 'Laboratorio Multimedia 202', 'capacity' => 25],
            ['name' => 'Aula 305 - Diseño y Web', 'capacity' => 28],
            ['name' => 'Laboratorio de Cómputo 401', 'capacity' => 20],
        ];

        $rooms = [];
        foreach ($definitions as $def) {
            $rooms[] = Classroom::query()->updateOrCreate(
                ['name' => $def['name']],
                ['capacity' => $def['capacity'], 'is_active' => true],
            );
        }

        return $rooms;
    }

    /** @return list<User> */
    private function seedTeachers(): array
    {
        $existing = User::role(Role::Docente->value)->where('is_active', true)->orderBy('id')->take(5)->get()->all();
        if (count($existing) >= 5) {
            return $existing;
        }

        $definitions = [
            ['name' => 'Prof. Lucía García Rojas', 'email' => 'docente01@openedu.test'],
            ['name' => 'Prof. Mateo Rodríguez Vargas', 'email' => 'docente02@openedu.test'],
            ['name' => 'Prof. Valentina Martínez Mendoza', 'email' => 'docente03@openedu.test'],
            ['name' => 'Prof. Santiago López Salazar', 'email' => 'docente04@openedu.test'],
            ['name' => 'Prof. Camila Hernández Herrera', 'email' => 'docente05@openedu.test'],
        ];

        $teachers = [];
        foreach ($definitions as $def) {
            $user = User::query()->updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => 'password',
                    'must_change_password' => false,
                    'is_active' => true,
                ],
            );
            $user->assignRole(Role::Docente->value);
            $teachers[] = $user;
        }

        return $teachers;
    }

    /** @return list<Course> */
    private function seedPublishedCourses(): array
    {
        $existing = Course::query()->where('status', 'published')->orderBy('id')->take(6)->get()->all();
        if (count($existing) >= 6) {
            return $existing;
        }

        $names = [
            'Python desde Cero',
            'JavaScript Moderno',
            'Diseño Web con HTML y CSS',
            'Bases de Datos con SQL',
            'Control de Versiones con Git y GitHub',
            'Análisis de Datos con Python',
        ];

        $courses = [];
        foreach ($names as $name) {
            $courses[] = Course::query()->firstOrCreate(
                ['name' => $name],
                [
                    'description' => "Curso institucional de {$name}.",
                    'objectives' => "Dominar las competencias prácticas de {$name}.",
                    'status' => 'published',
                ],
            );
        }

        return $courses;
    }
}
