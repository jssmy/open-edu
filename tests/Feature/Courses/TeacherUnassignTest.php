<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;

it('preserves course assessments when a teacher is unassigned', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $teacher = userWithRole(Role::Docente);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $course->teachers()->attach($teacher->id);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación inicial',
        'max_attempts' => 1,
        'passing_score' => 0,
        'total_score' => 0,
        'is_published' => false,
    ]);

    $this->actingAs($manager)->put("/courses/{$course->id}/teachers", ['teachers' => []])
        ->assertRedirect();

    $this->assertDatabaseMissing('course_teacher', [
        'course_id' => $course->id,
        'user_id' => $teacher->id,
    ]);
    $this->assertDatabaseHas('assessments', [
        'id' => $assessment->id,
        'course_id' => $course->id,
    ]);
});
