<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use Inertia\Testing\AssertableInertia as Assert;

it('denies access to /courses if teacher does not have courses.view permission', function () {
    $role = Spatie\Permission\Models\Role::findByName(Role::Docente->value);
    $role->revokePermissionTo('courses.view');

    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);

    $this->actingAs($teacher)->get('/courses')
        ->assertForbidden();
});

it('forbids teacher from editing a course they are not assigned to', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $otherTeacher = userWithRole(Role::Docente, ['must_change_password' => false]);

    $course = Course::factory()->create();
    $course->teachers()->attach($otherTeacher->id);

    $this->actingAs($teacher)->get("/courses/{$course->id}/edit")
        ->assertForbidden();

    $this->actingAs($teacher)->get("/courses/{$course->id}")
        ->assertNotFound(); // el view de docente no asignado da denyAsNotFound
});

it('allows teacher to edit a course they are assigned to', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher->id);

    $this->actingAs($teacher)->get("/courses/{$course->id}/edit")
        ->assertOk();

    $this->actingAs($teacher)->get("/courses/{$course->id}")
        ->assertOk();
});

it('does not allow teacher without courses.manage to manage course status or delete course', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $course = Course::factory()->create(['status' => 'draft']);
    $course->teachers()->attach($teacher->id);

    // En la vista de detalle, canManageCourses debe ser false
    $this->actingAs($teacher)->get("/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Show')
            ->where('canManageCourses', false)
            ->where('canManageContent', true)
        );

    // Intento de mutación de estado debe retornar 403
    $this->actingAs($teacher)->patch("/courses/{$course->id}/status", [
        'status' => 'published',
    ])->assertForbidden();

    expect($course->fresh()->status)->toBe('draft');

    // Intento de eliminación debe retornar 403
    $this->actingAs($teacher)->delete("/courses/{$course->id}")
        ->assertForbidden();

    expect($course->fresh())->not->toBeNull();
});

it('allows admin with courses.manage permission to manage course status and delete course', function () {
    $admin = userWithRole(Role::Administrador, ['must_change_password' => false]);
    $course = Course::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)->get("/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Show')
            ->where('canManageCourses', true)
        );

    $this->actingAs($admin)->patch("/courses/{$course->id}/status", [
        'status' => 'published',
    ])->assertRedirect();

    expect($course->fresh()->status)->toBe('published');

    $this->actingAs($admin)->delete("/courses/{$course->id}")
        ->assertRedirect('/courses');

    expect($course->fresh()->trashed())->toBeTrue();
});
