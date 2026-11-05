<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Inertia\Testing\AssertableInertia as Assert;

test('scheduling index sets default teacher to first available active teacher when no teacher param is given', function (): void {
    $admin = userWithRole(Role::Administrador);

    $teacher1 = userWithRole(Role::Docente, ['name' => 'Alice Teacher', 'is_active' => true]);
    $teacher2 = userWithRole(Role::Docente, ['name' => 'Bob Teacher', 'is_active' => true]);

    $this->actingAs($admin)
        ->get('/scheduling')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Scheduling/Index')
            ->where('filters.teacher', (string) $teacher1->id)
        );
});

test('scheduling index allows viewing all teachers when teacher param is empty string', function (): void {
    $admin = userWithRole(Role::Administrador);

    $teacher = userWithRole(Role::Docente, ['is_active' => true]);

    $this->actingAs($admin)
        ->get('/scheduling?teacher=')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Scheduling/Index')
            ->where('filters.teacher', '')
        );
});

test('scheduling index filters specifically by chosen teacher', function (): void {
    $admin = userWithRole(Role::Administrador);

    $teacher1 = userWithRole(Role::Docente, ['is_active' => true]);
    $teacher2 = userWithRole(Role::Docente, ['is_active' => true]);

    $course = Course::factory()->create();

    $offering1 = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'starts_on' => '2026-03-01',
        'ends_on' => '2026-06-30',
    ]);
    $offering1->teachers()->sync([$teacher1->id]);

    $offering2 = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'starts_on' => '2026-03-01',
        'ends_on' => '2026-06-30',
    ]);
    $offering2->teachers()->sync([$teacher2->id]);

    $this->actingAs($admin)
        ->get('/scheduling?teacher='.$teacher2->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Scheduling/Index')
            ->where('filters.teacher', (string) $teacher2->id)
            ->has('schedules.data', 1)
            ->where('schedules.data.0.id', $offering2->id)
        );
});
