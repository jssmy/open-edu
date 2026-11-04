<?php

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->manager = userWithRole(Role::Administrador));

function createRegisteredStudentForEnrollment(): int
{
    test()->actingAs(test()->manager)->post('/students', [
        'name' => 'Estudiante de prueba',
        'email' => fake()->unique()->safeEmail(),
        'temporary_password' => 'temporal-123',
    ])->assertRedirect('/students');

    return (int) DB::table('students')->latest('id')->value('id');
}

it('enrolls a student in a published course and exposes both cross-queries', function () {
    $studentId = createRegisteredStudentForEnrollment();
    $course = Course::factory()->create(['name' => 'Biología', 'status' => 'published']);

    $this->actingAs($this->manager)->post("/courses/{$course->id}/enrollments", [
        'student_id' => $studentId,
    ])->assertRedirect();

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $studentId,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('audit_logs', ['event' => 'enrollment.created']);

    $this->actingAs($this->manager)->get("/courses/{$course->id}/students")
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Enrollments')
            ->where('students.0.id', $studentId));
    $this->actingAs($this->manager)->get("/students/{$studentId}/courses")
        ->assertInertia(fn (Assert $page) => $page->component('Students/Show')
            ->where('courses.0.id', $course->id));
});

it('rejects duplicate enrollment and enrollment in a non-published course', function () {
    $studentId = createRegisteredStudentForEnrollment();
    $course = Course::factory()->create(['status' => 'published']);

    $this->actingAs($this->manager)->post("/courses/{$course->id}/enrollments", [
        'student_id' => $studentId,
    ])->assertRedirect();

    $this->actingAs($this->manager)->post("/courses/{$course->id}/enrollments", [
        'student_id' => $studentId,
    ])->assertSessionHasErrors('student_id');

    $draftCourse = Course::factory()->create(['status' => 'draft']);
    $this->actingAs($this->manager)->post("/courses/{$draftCourse->id}/enrollments", [
        'student_id' => $studentId,
    ])->assertSessionHasErrors('course');

    expect(DB::table('enrollments')->count())->toBe(1);
});

it('changes enrollment state and preserves the enrollment record', function () {
    $studentId = createRegisteredStudentForEnrollment();
    $course = Course::factory()->create(['status' => 'published']);
    $this->actingAs($this->manager)->post("/courses/{$course->id}/enrollments", [
        'student_id' => $studentId,
    ]);
    $enrollmentId = DB::table('enrollments')->value('id');

    $this->actingAs($this->manager)->patch("/enrollments/{$enrollmentId}/status", [
        'status' => 'suspended',
    ])->assertRedirect();
    $this->actingAs($this->manager)->patch("/enrollments/{$enrollmentId}/status", [
        'status' => 'cancelled',
    ])->assertRedirect();

    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollmentId,
        'status' => 'cancelled',
    ]);
});

it('lets teachers query enrolled students only for assigned courses', function () {
    $teacher = userWithRole(Role::Docente);
    $studentId = createRegisteredStudentForEnrollment();
    createRegisteredStudentForEnrollment();
    $course = Course::factory()->create(['status' => 'published']);
    $course->teachers()->attach($teacher->id);
    $this->actingAs($this->manager)->post("/courses/{$course->id}/enrollments", [
        'student_id' => $studentId,
    ]);

    $this->actingAs($teacher)->get("/courses/{$course->id}/students")
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Enrollments')
            ->where('students.0.id', $studentId)
            ->has('students', 1)
            ->where('availableStudents', []));
    $this->actingAs($teacher)->get('/students')
        ->assertInertia(fn (Assert $page) => $page->where('students.data.0.id', $studentId)
            ->has('students.data', 1));
    $enrollmentId = DB::table('enrollments')->where('student_id', $studentId)->value('id');
    $this->actingAs($teacher)->patch("/enrollments/{$enrollmentId}/status", [
        'status' => 'suspended',
    ])->assertForbidden();
    $studentUserId = (int) DB::table('students')->where('id', $studentId)->value('user_id');
    $studentUser = User::query()->findOrFail($studentUserId);
    $studentUser->update(['must_change_password' => false]);
    $this->actingAs($studentUser)->patch("/enrollments/{$enrollmentId}/status", [
        'status' => 'suspended',
    ])->assertForbidden();
    $unassignedCourse = Course::factory()->create(['status' => 'published']);
    $this->actingAs($teacher)->get("/courses/{$unassignedCourse->id}/students")->assertNotFound();
    $this->actingAs($teacher)->post("/courses/{$course->id}/enrollments", [])->assertForbidden();
});
