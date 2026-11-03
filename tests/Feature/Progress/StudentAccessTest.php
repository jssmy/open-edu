<?php

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array{0: User, 1: Student} */
function studentWithCourseEnrollment(
    Course $course,
    EnrollmentStatus $status = EnrollmentStatus::Active,
): array {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now(),
    ]);

    return [$user, $student];
}

it('shows a published course to an actively enrolled student', function () {
    $course = Course::factory()->create(['status' => 'published']);
    [$user] = studentWithCourseEnrollment($course);

    $this->actingAs($user)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.id', $course->id)
            ->where('course.progress.percentage', 0));
});

it('denies access when enrollment is not active', function (EnrollmentStatus $status) {
    $course = Course::factory()->create(['status' => 'published']);
    [$user] = studentWithCourseEnrollment($course, $status);

    $this->actingAs($user)->get("/my/courses/{$course->id}")->assertForbidden();
})->with([
    'suspended enrollment' => EnrollmentStatus::Suspended,
    'cancelled enrollment' => EnrollmentStatus::Cancelled,
]);

it('denies access when the student has no enrollment', function () {
    $course = Course::factory()->create(['status' => 'published']);
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    Student::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get("/my/courses/{$course->id}")->assertForbidden();
});

it('denies access to a deactivated course', function () {
    $course = Course::factory()->create(['status' => 'deactivated']);
    [$user] = studentWithCourseEnrollment($course);

    $this->actingAs($user)->get("/my/courses/{$course->id}")->assertForbidden();
});

it('lists only the current student\'s active published courses', function () {
    $course = Course::factory()->create(['status' => 'published']);
    $unpublishedCourse = Course::factory()->create(['status' => 'deactivated']);
    $otherCourse = Course::factory()->create(['status' => 'published']);
    [$user, $student] = studentWithCourseEnrollment($course);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $unpublishedCourse->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $otherUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $otherStudent = Student::factory()->create(['user_id' => $otherUser->id]);
    Enrollment::query()->create([
        'student_id' => $otherStudent->id,
        'course_id' => $otherCourse->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($user)->get('/my/courses')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/MyCourses')
            ->has('courses', 1)
            ->where('courses.0.id', $course->id));
});

it('forbids completing a lesson without course access', function () {
    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->for($unit)->create();
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    Student::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")->assertForbidden();
});

it('serves lesson files only to students with course access', function () {
    Storage::fake('local');
    $course = Course::factory()->create(['status' => 'published']);
    [$user] = studentWithCourseEnrollment($course);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $filePath = 'courses/'.$course->id.'/lessons/guide.pdf';
    $lesson = Lesson::factory()->for($unit)->create([
        'content_type' => 'document',
        'content' => $filePath,
    ]);
    Storage::disk('local')->put($filePath, 'Lesson document');
    $otherUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    Student::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)->get("/lessons/{$lesson->id}/content")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    $this->actingAs($otherUser)->get("/lessons/{$lesson->id}/content")->assertForbidden();
});
