<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;

it('allows an assigned teacher to manage course content', function () {
    $teacher = userWithRole(Role::Docente);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $course->teachers()->attach($teacher->id);

    $this->actingAs($teacher)->post("/courses/{$course->id}/units", [
        'title' => 'Unidad del docente',
        'position' => 1,
    ])->assertRedirect();
});

it('returns 404 when a teacher accesses an unassigned course', function () {
    $teacher = userWithRole(Role::Docente);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($teacher)->get("/courses/{$course->id}")->assertNotFound();
});

it('forbids an assigned teacher from changing course status', function () {
    $teacher = userWithRole(Role::Docente);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $course->teachers()->attach($teacher->id);

    $this->actingAs($teacher)->patch("/courses/{$course->id}/status", [
        'status' => 'published',
    ])->assertForbidden();
});
