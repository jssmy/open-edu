<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Application\GetStudentClasses;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use Database\Seeders\FinishedCourseOfferingsSeeder;

it('seeds finished course offerings with past dates and enrolled students', function () {
    $user = userWithRole(Role::Docente, ['is_active' => true]);
    $studentUser = userWithRole(Role::Estudiante, ['is_active' => true]);
    $student = Student::factory()->create(['user_id' => $studentUser->id, 'is_active' => true]);

    $this->seed(FinishedCourseOfferingsSeeder::class);

    $finishedOfferings = CourseOffering::query()
        ->where('ends_on', '<', now()->toDateString())
        ->get();

    expect($finishedOfferings->count())->toBeGreaterThanOrEqual(3);

    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('status', EnrollmentStatus::Active)
        ->whereHas('courseOffering', fn ($q) => $q->where('ends_on', '<', now()->toDateString()))
        ->first();

    expect($enrollment)->not->toBeNull();

    $getStudentClasses = app(GetStudentClasses::class);
    $data = $getStudentClasses($student->id);

    $finishedClasses = array_values(array_filter($data['classes'], fn ($c) => $c['status'] === 'finished'));

    expect($finishedClasses)->not->toBeEmpty()
        ->and($finishedClasses[0]['is_finished'])->toBeTrue()
        ->and($finishedClasses[0]['can_access'])->toBeTrue();

    // Verify finished course has assessment with submitted attempts
    $finishedWithAssessment = Course::query()
        ->where('name', 'Fundamentos de Algoritmos y Lógica de Programación')
        ->firstOrFail();

    expect($finishedWithAssessment->assessments()->exists())->toBeTrue();
    $assessment = $finishedWithAssessment->assessments()->firstOrFail();
    expect($assessment->attempts()->where('student_id', $student->id)->exists())->toBeTrue();
});
