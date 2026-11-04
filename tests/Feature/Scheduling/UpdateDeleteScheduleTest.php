<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;

it('updates schedule, reassigns course_teacher and releases classroom when switching to virtual', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $period = AcademicPeriod::factory()->create();
    $classroom = Classroom::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher1->id,
        'academic_period_id' => $period->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
        'blocks' => [['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ])->assertRedirect();

    $schedule = CourseSchedule::query()->firstOrFail();

    $this->actingAs($admin)->put("/scheduling/schedules/{$schedule->id}", [
        'course_id' => $course->id,
        'teacher_id' => $teacher2->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'classroom_id' => $classroom->id,
        'blocks' => [['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_schedules', [
        'id' => $schedule->id,
        'teacher_id' => $teacher2->id,
        'modality' => 'virtual',
        'classroom_id' => null,
    ]);
    $this->assertDatabaseMissing('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher1->id]);
    $this->assertDatabaseHas('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher2->id]);
});

it('blocks editing and deleting schedules in an ended academic period (FR-014)', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $endedPeriod = AcademicPeriod::factory()->ended()->create();

    $schedule = CourseSchedule::factory()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $endedPeriod->id,
        'modality' => 'virtual',
    ]);

    $this->actingAs($admin)->put("/scheduling/schedules/{$schedule->id}", [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $endedPeriod->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ])->assertSessionHasErrors('academic_period_id');

    $this->actingAs($admin)->delete("/scheduling/schedules/{$schedule->id}")
        ->assertSessionHasErrors('academic_period_id');
});

it('deletes a schedule and removes teacher assignment when no other schedule remains', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 4, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ])->assertRedirect();

    $schedule = CourseSchedule::query()->firstOrFail();

    $this->actingAs($admin)->delete("/scheduling/schedules/{$schedule->id}")->assertRedirect();

    $this->assertDatabaseMissing('course_schedules', ['id' => $schedule->id]);
    $this->assertDatabaseMissing('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher->id]);
});

it('filters schedules and shows teacher own schedule on /my/schedule', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create(['name' => 'Física I'])->id,
        'teacher_id' => $teacher1->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ]);
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create(['name' => 'Química I'])->id,
        'teacher_id' => $teacher2->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 2, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ]);

    $adminResponse = $this->actingAs($admin)->get("/scheduling?teacher={$teacher1->id}")->assertOk();
    expect(collect($adminResponse->viewData('page')['props']['schedules']['data'])->pluck('course_name')->all())
        ->toEqual(['Física I']);

    $teacherResponse = $this->actingAs($teacher1)->get('/my/schedule')->assertOk();
    expect(collect($teacherResponse->viewData('page')['props']['schedules'])->pluck('course_name')->all())
        ->toEqual(['Física I']);
});
