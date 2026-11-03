<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use Inertia\Testing\AssertableInertia as Assert;

it('creates courses in draft status for an authorized course manager', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => 'Curso introductorio',
        'objectives' => 'Comprender los fundamentos de biología',
    ])->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'name' => 'Biología',
        'status' => 'draft',
    ]);
});

it('opens the new course page without resolving create as a course id', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->get('/courses/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Edit')
            ->where('course', null));
});

it('updates and shows a course', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Curso anterior',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $course->refresh();

    $this->actingAs($manager)->put("/courses/{$course->id}", [
        'name' => 'Curso actualizado',
        'description' => 'Nueva descripción',
        'objectives' => 'Nuevos objetivos',
        'lock_version' => $course->lock_version,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($course->fresh()->name)->toBe('Curso actualizado');
    $this->actingAs($manager)->get("/courses/{$course->id}")->assertOk();
});

it('publishes and deactivates a course', function (string $status) {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->patch("/courses/{$course->id}/status", [
        'status' => $status,
    ])->assertRedirect();

    expect($course->fresh()->status)->toBe($status);
})->with([
    'published',
    'deactivated',
]);

it('soft deletes a course without enrollments', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->delete("/courses/{$course->id}")->assertRedirect();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('assigns teachers to a course', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $teacher = userWithRole(Role::Docente);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->put("/courses/{$course->id}/teachers", [
        'teachers' => [$teacher->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('course_teacher', [
        'course_id' => $course->id,
        'user_id' => $teacher->id,
    ]);
});

it('forbids students from managing courses', function () {
    $student = userWithRole(Role::Estudiante);

    $this->actingAs($student)->post('/courses', [])->assertForbidden();
});
