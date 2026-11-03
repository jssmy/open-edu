<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;

it('creates units and text lessons', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->post("/courses/{$course->id}/units", [
        'title' => 'Unidad 1',
        'position' => 1,
    ])->assertRedirect();

    $unit = Unit::query()->where('course_id', $course->id)->firstOrFail();
    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Introducción',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido de introducción',
    ])->assertRedirect();

    $this->assertDatabaseHas('lessons', [
        'unit_id' => $unit->id,
        'title' => 'Introducción',
        'content_type' => 'text',
        'content' => 'Contenido de introducción',
    ]);
});

it('persists the requested order of course units', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $first = Unit::query()->create(['course_id' => $course->id, 'title' => 'Primera', 'position' => 1]);
    $second = Unit::query()->create(['course_id' => $course->id, 'title' => 'Segunda', 'position' => 2]);

    $this->actingAs($manager)->patch('/units/reorder', [
        'unit_ids' => [$second->id, $first->id],
    ])->assertRedirect();

    expect($second->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2);
});

it('persists the requested order of lessons within a unit', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);
    $first = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Primera',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);
    $second = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Segunda',
        'position' => 2,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);

    $this->actingAs($manager)->patch('/lessons/reorder', [
        'lesson_ids' => [$second->id, $first->id],
    ])->assertRedirect();

    expect($second->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2);
});

it('archives a lesson and retains it in storage', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);
    $lesson = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Lección',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);

    $this->actingAs($manager)->patch("/lessons/{$lesson->id}/archive")->assertRedirect();

    expect($lesson->fresh()->is_archived)->toBeTrue();
});
