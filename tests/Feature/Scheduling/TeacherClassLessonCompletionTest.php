<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;

it('allows assigned teacher to mark and toggle lesson completion for course offering', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 1]);

    $offering = CourseOffering::query()->create([
        'course_id' => $course->id,
        'group_code' => 'GRUPO-A',
        'status' => 'published',
    ]);
    $offering->teachers()->attach($teacher->id);

    // 1. Marcar lección como completada
    $response = $this->actingAs($teacher)->postJson("/lessons/{$lesson->id}/complete", [
        'course_offering_id' => $offering->id,
        'action' => 'complete',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('lesson_progress', [
        'course_offering_id' => $offering->id,
        'lesson_id' => $lesson->id,
        'user_id' => $teacher->id,
    ]);

    // 2. Alternar (toggle / uncomplete)
    $responseToggle = $this->actingAs($teacher)->postJson("/lessons/{$lesson->id}/complete", [
        'course_offering_id' => $offering->id,
        'action' => 'toggle',
    ]);

    $responseToggle->assertOk();
    $this->assertDatabaseMissing('lesson_progress', [
        'course_offering_id' => $offering->id,
        'lesson_id' => $lesson->id,
    ]);
});

it('forbids unassigned teacher from marking lesson completion for course offering', function () {
    $assignedTeacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $otherTeacher = userWithRole(Role::Docente, ['must_change_password' => false]);

    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 1]);

    $offering = CourseOffering::query()->create([
        'course_id' => $course->id,
        'group_code' => 'GRUPO-B',
        'status' => 'published',
    ]);
    $offering->teachers()->attach($assignedTeacher->id);

    $this->actingAs($otherTeacher)->postJson("/lessons/{$lesson->id}/complete", [
        'course_offering_id' => $offering->id,
        'action' => 'complete',
    ])->assertForbidden();

    $this->assertDatabaseMissing('lesson_progress', [
        'course_offering_id' => $offering->id,
        'lesson_id' => $lesson->id,
    ]);
});
