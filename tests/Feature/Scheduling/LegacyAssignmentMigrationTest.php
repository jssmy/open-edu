<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;

it('lists pending schedules without period and completes them when scheduled', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['name' => 'Álgebra Lineal']);
    $period = AcademicPeriod::factory()->create();

    $pending = CourseSchedule::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => null,
        'modality' => 'virtual',
        'classroom_id' => null,
    ]);
    $course->teachers()->sync([$teacher->id]);

    $response = $this->actingAs($admin)->get('/scheduling/pending')->assertOk();
    expect($response->viewData('page')['props']['pending']['data'][0]['course_name'])->toBe('Álgebra Lineal');

    $this->actingAs($admin)->put("/scheduling/schedules/{$pending->id}", [
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 5, 'starts_at' => '14:00', 'ends_at' => '16:00']],
    ])->assertRedirect();

    expect(CourseSchedule::query()->whereNull('academic_period_id')->count())->toBe(0);
    $this->assertDatabaseHas('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher->id]);
});
