<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;

it('creates an in-person course offering with a single teacher, dated sessions, syncs course_teacher and rejects multiple teachers per group', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $classroom = Classroom::factory()->create(['capacity' => 35]);

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-2026-A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2027-02-28',
        'max_students' => 25,
        'modality' => 'in_person',
        'status' => 'published',
        'teacher_ids' => [$teacher1->id, $teacher2->id],
        'sessions' => [
            [
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'classroom_id' => $classroom->id,
                'status' => 'scheduled',
            ],
        ],
    ])->assertSessionHasErrors('teacher_ids');

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-2026-A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2027-02-28',
        'max_students' => 25,
        'modality' => 'in_person',
        'status' => 'published',
        'teacher_id' => $teacher1->id,
        'sessions' => [
            [
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'classroom_id' => $classroom->id,
                'status' => 'scheduled',
            ],
            [
                'session_date' => '2026-11-12',
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'classroom_id' => $classroom->id,
                'status' => 'scheduled',
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_offerings', [
        'course_id' => $course->id,
        'group_code' => 'GRP-2026-A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2027-02-28',
        'max_students' => 25,
        'modality' => 'in_person',
        'status' => 'published',
    ]);
    $this->assertDatabaseCount('course_sessions', 2);
    $this->assertDatabaseHas('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher1->id]);
});

it('creates virtual and hybrid course offerings with video conference link', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();

    $this->actingAs($admin)->post('/scheduling/offerings', [
        'course_id' => $course->id,
        'group_code' => 'GRP-VIRT-01',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-31',
        'max_students' => 40,
        'modality' => 'virtual',
        'status' => 'published',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            [
                'title' => 'Sesión virtual 1',
                'session_date' => '2026-11-15',
                'starts_at' => '14:00',
                'ends_at' => '16:00',
                'teacher_id' => $teacher->id,
                'meeting_url' => 'https://meet.openedu.test/grp-virt-01',
                'status' => 'scheduled',
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_offerings', [
        'course_id' => $course->id,
        'group_code' => 'GRP-VIRT-01',
        'modality' => 'virtual',
    ]);
    $this->assertDatabaseHas('course_sessions', [
        'title' => 'Sesión virtual 1',
        'classroom_id' => null,
        'meeting_url' => 'https://meet.openedu.test/grp-virt-01',
    ]);
});

it('rejects an in-person offering session without classroom', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-NO-ROOM',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 20,
        'modality' => 'in_person',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            [
                'title' => 'Sesión sin aula',
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'teacher_id' => $teacher->id,
                'classroom_id' => null,
            ],
        ],
    ])->assertSessionHasErrors('classroom_id');
});

it('rejects invalid session time range or non-teacher user', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $student = userWithRole(Role::Estudiante);
    $course = Course::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-ERR-TIME',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 20,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            [
                'title' => 'Sesión inválida',
                'session_date' => '2026-11-10',
                'starts_at' => '10:00',
                'ends_at' => '09:00',
                'teacher_id' => $teacher->id,
            ],
        ],
    ])->assertSessionHasErrors('sessions');

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-ERR-TEACHER',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 20,
        'modality' => 'virtual',
        'teacher_ids' => [$student->id],
        'sessions' => [
            [
                'title' => 'Sesión rol inválido',
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'teacher_id' => $student->id,
            ],
        ],
    ])->assertSessionHasErrors('teacher_id');
});

it('auto-generates sequential group_code per course when omitted or empty', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => '',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-31',
        'max_students' => 30,
        'modality' => 'virtual',
        'status' => 'published',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            [
                'title' => 'Sesión 1',
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'teacher_id' => $teacher->id,
                'status' => 'scheduled',
            ],
        ],
    ])->assertRedirect();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-31',
        'max_students' => 30,
        'modality' => 'virtual',
        'status' => 'published',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            [
                'title' => 'Sesión 1',
                'session_date' => '2026-11-11',
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'teacher_id' => $teacher->id,
                'status' => 'scheduled',
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_offerings', [
        'course_id' => $course->id,
        'group_code' => 'GRP-01',
    ]);
    $this->assertDatabaseHas('course_offerings', [
        'course_id' => $course->id,
        'group_code' => 'GRP-02',
    ]);
});

it('forbids users without scheduling.manage from accessing scheduling endpoints', function () {
    $teacher = userWithRole(Role::Docente);

    $this->actingAs($teacher)->get('/scheduling')->assertForbidden();
    $this->actingAs($teacher)->post('/scheduling/schedules', [])->assertForbidden();
});
