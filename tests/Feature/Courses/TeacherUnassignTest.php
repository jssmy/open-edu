<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;

it('preserves course assessments when a teacher schedule is deleted', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $schedule = CourseSchedule::factory()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
        'modality' => 'virtual',
    ]);
    $course->teachers()->sync([$teacher->id]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación inicial',
        'max_attempts' => 1,
        'passing_score' => 0,
        'total_score' => 0,
        'is_published' => false,
    ]);

    $this->actingAs($admin)->delete("/scheduling/schedules/{$schedule->id}")
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
