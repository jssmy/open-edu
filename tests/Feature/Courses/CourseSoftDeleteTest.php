<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('requires confirmation to soft-delete a course with enrollments and preserves their history', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $studentUser = userWithRole(Role::Estudiante);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);
    $course = Course::factory()->create(['status' => 'published']);
    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $this->actingAs($manager)->delete("/courses/{$course->id}")
        ->assertSessionHasErrors('confirm_impact');
    expect($course->fresh()->deleted_at)->toBeNull();

    $this->actingAs($manager)->delete("/courses/{$course->id}", ['confirm_impact' => true])
        ->assertRedirect('/courses');

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
    expect(Enrollment::query()->whereKey($enrollment->id)->exists())->toBeTrue()
        ->and(DB::table('enrollments')->where('course_id', $course->id)->count())->toBe(1);
    $this->actingAs($manager)->get('/courses')->assertDontSee($course->name);
    $this->actingAs($manager)->get("/students/{$student->id}/courses")
        ->assertInertia(fn (Assert $page) => $page->where('courses.0.name', $course->name)
            ->where('courses.0.status', 'active'));
});
