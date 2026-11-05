<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

function setupClassWithDates(?string $startsOn, ?string $endsOn): array
{
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);

    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->for($unit)->create(['is_archived' => false]);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'group_code' => 'GRP-01',
        'starts_on' => $startsOn,
        'ends_on' => $endsOn,
        'status' => 'published',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación de Prueba',
        'is_published' => true,
        'max_attempts' => 3,
        'total_score' => 20,
        'passing_score' => 11,
    ]);
    Question::query()->create([
        'assessment_id' => $assessment->id,
        'statement' => richTextDocument('Pregunta 1'),
        'points' => 20,
        'position' => 1,
    ]);

    return [$user, $student, $course, $offering, $lesson, $assessment];
}

it('denies course access if offering has not started yet', function () {
    [$user, $student, $course] = setupClassWithDates(
        startsOn: now()->addDays(5)->toDateString(),
        endsOn: now()->addDays(30)->toDateString()
    );

    $this->actingAs($user)->get("/my/courses/{$course->id}")
        ->assertForbidden();
});

it('allows course access in read-only mode if offering has finished but forbids lesson completion and taking assessments', function () {
    [$user, $student, $course, $offering, $lesson, $assessment] = setupClassWithDates(
        startsOn: now()->subDays(30)->toDateString(),
        endsOn: now()->subDays(5)->toDateString()
    );

    // Puede acceder a consultar el contenido en modo lectura
    $this->actingAs($user)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.id', $course->id)
            ->where('isReadOnly', true)
            ->where('course.is_finished', true));

    // Prohibido completar lecciones
    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")
        ->assertForbidden();

    // Prohibido rendir evaluación (GET o POST attempt)
    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")
        ->assertForbidden();
});
