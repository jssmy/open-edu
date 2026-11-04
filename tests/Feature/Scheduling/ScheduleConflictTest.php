<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;

it('rejects overlapping teacher sessions on the same date and allows consecutive or cancelled sessions', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'group_code' => 'GRP-1',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 25,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            ['title' => 'S1', 'session_date' => '2026-11-10', 'starts_at' => '08:00', 'ends_at' => '10:00', 'teacher_id' => $teacher->id],
        ],
    ])->assertRedirect();

    // Traslape para el mismo docente en la misma fecha
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'group_code' => 'GRP-2',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 25,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            ['title' => 'S2', 'session_date' => '2026-11-10', 'starts_at' => '09:00', 'ends_at' => '11:00', 'teacher_id' => $teacher->id],
        ],
    ])->assertSessionHasErrors('teacher_id');

    // Consecutivo en la misma fecha: permitido
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'group_code' => 'GRP-3',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 25,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            ['title' => 'S3', 'session_date' => '2026-11-10', 'starts_at' => '10:00', 'ends_at' => '12:00', 'teacher_id' => $teacher->id],
        ],
    ])->assertRedirect();
});

it('rejects classroom conflicts on the same date and time but allows virtual or different dates', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);
    $classroom = Classroom::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'group_code' => 'GRP-A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 30,
        'modality' => 'in_person',
        'teacher_ids' => [$teacher1->id],
        'sessions' => [
            [
                'title' => 'Clase A',
                'session_date' => '2026-11-12',
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'teacher_id' => $teacher1->id,
                'classroom_id' => $classroom->id,
            ],
        ],
    ])->assertRedirect();

    // Conflicto de aula misma fecha
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'group_code' => 'GRP-B',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 30,
        'modality' => 'in_person',
        'teacher_ids' => [$teacher2->id],
        'sessions' => [
            [
                'title' => 'Clase B',
                'session_date' => '2026-11-12',
                'starts_at' => '11:00',
                'ends_at' => '13:00',
                'teacher_id' => $teacher2->id,
                'classroom_id' => $classroom->id,
            ],
        ],
    ])->assertSessionHasErrors('classroom_id');

    // Misma aula en otra fecha: permitido
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'group_code' => 'GRP-C',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 30,
        'modality' => 'in_person',
        'teacher_ids' => [$teacher1->id],
        'sessions' => [
            [
                'title' => 'Clase C',
                'session_date' => '2026-11-13',
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'teacher_id' => $teacher1->id,
                'classroom_id' => $classroom->id,
            ],
        ],
    ])->assertRedirect();
});
