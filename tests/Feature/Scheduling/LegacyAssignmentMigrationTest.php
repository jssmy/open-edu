<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;

it('lists pending offerings without dates and completes them when scheduled', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['name' => 'Álgebra Lineal']);

    $pending = CourseOffering::query()->create([
        'course_id' => $course->id,
        'group_code' => 'PEND-'.$teacher->id,
        'starts_on' => null,
        'ends_on' => null,
        'max_students' => 30,
        'modality' => 'virtual',
        'status' => 'draft',
    ]);
    $pending->teachers()->sync([$teacher->id]);
    $course->teachers()->sync([$teacher->id]);

    $response = $this->actingAs($admin)->get('/scheduling/pending')->assertOk();
    expect($response->viewData('page')['props']['pending']['data'][0]['course_name'])->toBe('Álgebra Lineal');

    $this->actingAs($admin)->put("/scheduling/schedules/{$pending->id}", [
        'course_id' => $course->id,
        'group_code' => 'ALG-01',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-31',
        'max_students' => 30,
        'modality' => 'virtual',
        'status' => 'published',
        'teacher_ids' => [$teacher->id],
        'sessions' => [
            ['title' => 'Sesión 1', 'session_date' => '2026-11-15', 'starts_at' => '14:00', 'ends_at' => '16:00', 'teacher_id' => $teacher->id],
        ],
    ])->assertRedirect();

    expect(CourseOffering::query()->whereNull('starts_on')->count())->toBe(0);
    $this->assertDatabaseHas('course_teacher', ['course_id' => $course->id, 'user_id' => $teacher->id]);
});
