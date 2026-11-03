<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;

it('allows submitting an attempt started before the course was deactivated', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación en curso',
        'max_attempts' => 1,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => true,
    ]);
    $question = $assessment->questions()->create([
        'statement' => 'Pregunta',
        'points' => 1,
        'position' => 1,
    ]);
    $choice = $question->choices()->create(['text' => 'Correcta', 'is_correct' => true]);

    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertOk();
    $course->update(['status' => 'deactivated']);

    $this->actingAs($user)->post("/assessments/{$assessment->id}/attempts", [
        'answers' => [$question->id => $choice->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('attempts', [
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'score' => 1,
    ]);
    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertForbidden();

    $this->assertDatabaseCount('attempts', 1);
});
