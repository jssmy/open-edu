<?php

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->manager = userWithRole(Role::GestorDeCursos));

it('registers a student account and profile with a forced password change', function () {
    $this->actingAs($this->manager)->post('/students', [
        'name' => 'Ana Estudiante',
        'email' => 'ana@example.test',
        'temporary_password' => 'temporal-123',
        'identifier' => 'ST-100',
        'phone' => '+56 9 1234 5678',
    ])->assertRedirect('/students');

    $student = User::query()->where('email', 'ana@example.test')->firstOrFail();

    expect($student->hasRole(Role::Estudiante->value))->toBeTrue()
        ->and($student->must_change_password)->toBeTrue()
        ->and($student->is_active)->toBeTrue();

    $this->assertDatabaseHas('students', [
        'user_id' => $student->id,
        'identifier' => 'ST-100',
        'phone' => '+56 9 1234 5678',
        'is_active' => true,
    ]);
});

it('updates a student profile and account', function () {
    $this->actingAs($this->manager)->post('/students', [
        'name' => 'Ana',
        'email' => 'ana@example.test',
        'temporary_password' => 'temporal-123',
    ]);
    $studentId = DB::table('students')->where('identifier', null)->value('id');

    $this->actingAs($this->manager)->put("/students/{$studentId}", [
        'name' => 'Ana Actualizada',
        'email' => 'ana.nueva@example.test',
        'identifier' => 'ST-101',
        'phone' => '+56 9 8765 4321',
    ])->assertRedirect();

    expect(User::query()->where('email', 'ana.nueva@example.test')->value('name'))
        ->toBe('Ana Actualizada');
    $this->assertDatabaseHas('students', [
        'id' => $studentId,
        'identifier' => 'ST-101',
        'phone' => '+56 9 8765 4321',
    ]);
});

it('deactivates the student profile and its login account without deleting history', function () {
    $this->actingAs($this->manager)->post('/students', [
        'name' => 'Ana',
        'email' => 'ana@example.test',
        'temporary_password' => 'temporal-123',
    ]);
    $student = User::query()->where('email', 'ana@example.test')->firstOrFail();
    $studentId = DB::table('students')->where('user_id', $student->id)->value('id');
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $studentId,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $this->actingAs($this->manager)->patch("/students/{$studentId}/status", [
        'is_active' => false,
    ])->assertRedirect();

    expect($student->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseHas('students', ['id' => $studentId, 'is_active' => false]);
    $this->assertDatabaseHas('enrollments', ['student_id' => $studentId, 'course_id' => $course->id]);
});

it('allows teachers to read only students enrolled in their assigned courses', function () {
    $teacher = userWithRole(Role::Docente);
    $this->actingAs($this->manager)->post('/students', [
        'name' => 'Ana',
        'email' => 'ana@example.test',
        'temporary_password' => 'temporal-123',
    ]);
    $student = User::query()->where('email', 'ana@example.test')->firstOrFail();
    $studentId = DB::table('students')->where('user_id', $student->id)->value('id');
    $course = Course::factory()->create(['status' => 'published']);
    $course->teachers()->attach($teacher->id);
    DB::table('enrollments')->insert([
        'student_id' => $studentId,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($teacher)->get("/students/{$studentId}")->assertOk();
    $this->actingAs($teacher)->put("/students/{$studentId}", [])->assertForbidden();
    $this->actingAs($teacher)->get('/students')->assertOk();
});

it('paginates and filters the student list', function () {
    foreach (range(1, 12) as $i) {
        $this->actingAs($this->manager)->post('/students', [
            'name' => "Alumno {$i}", 'email' => "a{$i}@example.test", 'temporary_password' => 'temporal-123',
            'identifier' => "ID-{$i}",
        ]);
    }

    $this->actingAs($this->manager)->get('/students')->assertInertia(fn ($page) => $page
        ->has('students.data', 10)
        ->where('students.meta.total', 12));

    $this->actingAs($this->manager)->get('/students?q=ID-12&status=active')->assertInertia(fn ($page) => $page
        ->has('students.data', 1)
        ->where('students.data.0.name', 'Alumno 12'));
});
