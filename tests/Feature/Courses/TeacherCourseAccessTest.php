<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;

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
