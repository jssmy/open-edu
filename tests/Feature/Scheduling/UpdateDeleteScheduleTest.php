<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;

it('updates offering freely across months, reassigns course_teacher, releases classroom on virtual and manages individual sessions', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $classroom = Classroom::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-01',
        'starts_on' => '2026-11-01',
        'ends_on' => '2027-01-31',
        'max_students' => 25,
        'modality' => 'in_person',
        'teacher_ids' => [$teacher1->id],
        'sessions' => [
            [
                'title' => 'Sesión 1',
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'teacher_id' => $teacher1->id,
                'classroom_id' => $classroom->id,
            ],
        ],
    ])->assertRedirect();

    $offering = CourseOffering::query()->firstOrFail();

    $this->actingAs($admin)->put("/scheduling/schedules/{$offering->id}", [
        'course_id' => $course->id,
        'group_code' => 'GRP-01',
        'starts_on' => '2026-11-01',
        'ends_on' => '2027-03-31',
        'max_students' => 35,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher2->id],
        'sessions' => [
            [
                'title' => 'Sesión 1 editada',
                'session_date' => '2026-11-10',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'teacher_id' => $teacher2->id,
                'classroom_id' => $classroom->id,
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_offerings', [
        'id' => $offering->id,
        'max_students' => 35,
        'modality' => 'virtual',
    ]);
    $this->assertDatabaseHas('course_sessions', [
        'course_offering_id' => $offering->id,
        'title' => 'Sesión 1 editada',
        'classroom_id' => null,
    ]);
    $this->assertDatabaseMissing('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher1->id]);
    $this->assertDatabaseHas('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher2->id]);

    // Agregar y eliminar una sesión individual manualmente
    $this->actingAs($admin)->post("/scheduling/offerings/{$offering->id}/sessions", [
        'title' => 'Sesión 2 manual',
        'session_date' => '2026-11-17',
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'teacher_id' => $teacher2->id,
        'meeting_url' => 'https://meet.openedu.test/s2',
    ])->assertRedirect();

    $secondSession = CourseSession::query()->where('title', 'Sesión 2 manual')->firstOrFail();

    $this->actingAs($admin)->delete("/scheduling/sessions/{$secondSession->id}")->assertRedirect();
    $this->assertDatabaseMissing('course_sessions', ['id' => $secondSession->id]);
});

it('deletes an offering and removes teacher assignment when no other offering remains', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'group_code' => 'GRP-DEL',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 20,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            ['title' => 'S1', 'session_date' => '2026-11-12', 'starts_at' => '08:00', 'ends_at' => '10:00', 'teacher_id' => $teacher->id],
        ],
    ])->assertRedirect();

    $offering = CourseOffering::query()->firstOrFail();

    $this->actingAs($admin)->delete("/scheduling/schedules/{$offering->id}")->assertRedirect();

    $this->assertDatabaseMissing('course_offerings', ['id' => $offering->id]);
    $this->assertDatabaseMissing('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher->id]);
});

it('filters offerings and shows teacher own schedule on /my/schedule', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create(['name' => 'Física I'])->id,
        'group_code' => 'FIS-A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 25,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher1->id],
        'sessions' => [
            ['title' => 'Física S1', 'session_date' => '2026-11-10', 'starts_at' => '08:00', 'ends_at' => '10:00', 'teacher_id' => $teacher1->id],
        ],
    ]);
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create(['name' => 'Química I'])->id,
        'group_code' => 'QUI-A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-01',
        'max_students' => 25,
        'modality' => 'virtual',
        'teacher_ids' => [$teacher2->id],
        'sessions' => [
            ['title' => 'Química S1', 'session_date' => '2026-11-11', 'starts_at' => '08:00', 'ends_at' => '10:00', 'teacher_id' => $teacher2->id],
        ],
    ]);

    $adminResponse = $this->actingAs($admin)->get("/scheduling?teacher={$teacher1->id}")->assertOk();
    expect(collect($adminResponse->viewData('page')['props']['schedules']['data'])->pluck('course_name')->all())
        ->toEqual(['Física I']);

    $teacherResponse = $this->actingAs($teacher1)->get('/my/schedule')->assertOk();
    expect(collect($teacherResponse->viewData('page')['props']['schedules'])->pluck('course_name')->all())
        ->toEqual(['Física I']);
});
