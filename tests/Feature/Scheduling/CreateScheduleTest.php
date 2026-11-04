<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;

it('creates an in-person schedule with classroom and syncs course_teacher', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $period = AcademicPeriod::factory()->create();
    $classroom = Classroom::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
        'blocks' => [
            ['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00'],
            ['day_of_week' => 3, 'starts_at' => '08:00', 'ends_at' => '10:00'],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
    ]);
    $this->assertDatabaseCount('schedule_blocks', 2);
    $this->assertDatabaseHas('course_teacher', [
        'course_id' => $course->id,
        'user_id' => $teacher->id,
    ]);
});

it('creates a virtual schedule without classroom', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'classroom_id' => null,
        'blocks' => [
            ['day_of_week' => 2, 'starts_at' => '14:00', 'ends_at' => '16:00'],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'modality' => 'virtual',
        'classroom_id' => null,
    ]);
});

it('rejects an in-person schedule without classroom', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'in_person',
        'classroom_id' => null,
        'blocks' => [
            ['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00'],
        ],
    ])->assertSessionHasErrors('classroom_id');
});

it('rejects a schedule with invalid time range or non-teacher user', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $student = userWithRole(Role::Estudiante);
    $course = Course::factory()->create();
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [
            ['day_of_week' => 1, 'starts_at' => '10:00', 'ends_at' => '09:00'],
        ],
    ])->assertSessionHasErrors('blocks');

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $student->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [
            ['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00'],
        ],
    ])->assertSessionHasErrors('teacher_id');
});

it('forbids users without scheduling.manage from accessing scheduling endpoints', function () {
    $teacher = userWithRole(Role::Docente);

    $this->actingAs($teacher)->get('/scheduling')->assertForbidden();
    $this->actingAs($teacher)->post('/scheduling/schedules', [])->assertForbidden();
});

