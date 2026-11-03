<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use Inertia\Testing\AssertableInertia as Assert;

it('exposes the current course and lesson versions on their independent edit pages', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->for(Unit::factory()->for($course))->create();

    $this->actingAs($manager)->get("/courses/{$course->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Edit')
            ->where('course.lock_version', 1));

    $this->actingAs($manager)->get("/lessons/{$lesson->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Courses/LessonEdit')
            ->where('lesson.lock_version', 1));
});

it('returns HTTP 409 and preserves the latest course when a stale version is saved', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create(['name' => 'Curso original']);

    $this->actingAs($manager)->putJson("/courses/{$course->id}", [
        'name' => 'Curso actualizado',
        'description' => richTextDocument('Descripción actualizada'),
        'objectives' => richTextDocument('Objetivos actualizados'),
        'lock_version' => 1,
    ])->assertRedirect();

    $this->actingAs($manager)->putJson("/courses/{$course->id}", [
        'name' => 'Versión obsoleta',
        'description' => richTextDocument('Contenido obsoleto'),
        'objectives' => richTextDocument('Objetivos obsoletos'),
        'lock_version' => 1,
    ])->assertStatus(409)
        ->assertJsonPath('message', 'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.');

    expect($course->fresh()->name)->toBe('Curso actualizado')
        ->and($course->fresh()->description)->toEqual(richTextDocument('Descripción actualizada'))
        ->and($course->fresh()->lock_version)->toBe(2);
});

it('returns HTTP 409 and preserves the latest lesson when a stale version is saved', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $lesson = Lesson::factory()->create(['title' => 'Lección original']);

    $this->actingAs($manager)->putJson("/lessons/{$lesson->id}", [
        'title' => 'Lección actualizada',
        'position' => 1,
        'content_type' => 'text',
        'content' => richTextDocument('Contenido actualizado'),
        'lock_version' => 1,
    ])->assertRedirect();

    $this->actingAs($manager)->putJson("/lessons/{$lesson->id}", [
        'title' => 'Versión obsoleta',
        'position' => 1,
        'content_type' => 'text',
        'content' => richTextDocument('Contenido obsoleto'),
        'lock_version' => 1,
    ])->assertStatus(409)
        ->assertJsonPath('message', 'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.');

    expect($lesson->fresh()->title)->toBe('Lección actualizada')
        ->and($lesson->fresh()->content)->toEqual(richTextDocument('Contenido actualizado'))
        ->and($lesson->fresh()->lock_version)->toBe(2);
});
