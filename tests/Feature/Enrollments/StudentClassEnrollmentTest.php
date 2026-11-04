<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = userWithRole(Role::Administrador);
});

function createActiveStudentForEnrollment(string $name = 'Estudiante Activo'): int
{
    test()->actingAs(test()->admin)->post('/students', [
        'name' => $name,
        'email' => fake()->unique()->safeEmail(),
        'temporary_password' => 'temporal-123',
        'identifier' => fake()->unique()->numerify('DOC-#####'),
    ])->assertRedirect('/students');

    return (int) DB::table('students')->latest('id')->value('id');
}

it('restricts the enrollments module to users with the enrollments.manage permission (Admin by default)', function () {
    $courseManager = userWithRole(Role::GestorDeCursos);
    $teacher = userWithRole(Role::Docente);
    $studentUser = userWithRole(Role::Estudiante);

    $this->actingAs($courseManager)->get('/enrollments')->assertForbidden();
    $this->actingAs($teacher)->get('/enrollments')->assertForbidden();
    $this->actingAs($studentUser)->get('/enrollments')->assertForbidden();

    $this->actingAs($this->admin)->get('/enrollments')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Enrollments/Index'));
});

it('displays published courses with their academic offerings, sessions, teachers and available spots', function () {
    $studentId = createActiveStudentForEnrollment();
    $teacher = userWithRole(Role::Docente);
    $classroom = Classroom::query()->create(['name' => 'Aula 201', 'capacity' => 25, 'is_active' => true]);
    $course = Course::factory()->create(['name' => 'Cálculo Diferencial', 'status' => 'published']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'group_code' => 'Grupo A - Mañana',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 15,
        'modality' => 'in_person',
        'status' => 'published',
    ]);
    $offering->teachers()->sync([$teacher->id]);
    $offering->sessions()->create([
        'title' => 'Límites y continuidad',
        'session_date' => '2026-11-09',
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Enrollments/Index')
            ->where('selectedStudentId', $studentId)
            ->has('courses.data', 1)
            ->where('courses.meta.total', 1)
            ->where('courses.data.0.name', 'Cálculo Diferencial')
            ->where('courses.data.0.offerings.0.group_code', 'Grupo A - Mañana')
            ->where('courses.data.0.offerings.0.available_spots', 15)
            ->where('courses.data.0.offerings.0.sessions.0.title', 'Límites y continuidad')
            ->where('courses.data.0.offerings.0.sessions.0.classroom_name', 'Aula 201')
        );
});

it('orders published courses with available spots first and paginates the catalog', function () {
    $studentId = createActiveStudentForEnrollment('Estudiante Con Cupo');
    $occupiedStudentId = createActiveStudentForEnrollment('Estudiante Ocupado');
    $teacher = userWithRole(Role::Docente);

    $courseNoOfferings = Course::factory()->create(['name' => 'A - Sin programación', 'status' => 'published']);

    $courseFullOffering = Course::factory()->create(['name' => 'B - Cupo lleno', 'status' => 'published']);
    $fullOffering = CourseOffering::factory()->create([
        'course_id' => $courseFullOffering->id,
        'group_code' => 'GRP-01',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 1,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $fullOffering->teachers()->sync([$teacher->id]);
    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $occupiedStudentId,
        'course_offering_id' => $fullOffering->id,
    ])->assertRedirect();

    $courseWithSpots = Course::factory()->create(['name' => 'Z - Con vacantes disponibles', 'status' => 'published']);
    $openOffering = CourseOffering::factory()->create([
        'course_id' => $courseWithSpots->id,
        'group_code' => 'GRP-01',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 10,
        'modality' => 'in_person',
        'status' => 'published',
    ]);
    $openOffering->teachers()->sync([$teacher->id]);

    foreach (range(1, 5) as $idx) {
        Course::factory()->create(['name' => sprintf('M - Curso Extra %02d', $idx), 'status' => 'published']);
    }

    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 6)
            ->where('courses.meta.total', 8)
            ->where('courses.meta.page', 1)
            ->where('courses.meta.last_page', 2)
            ->where('courses.data.0.name', 'Z - Con vacantes disponibles')
            ->where('courses.data.0.total_available_spots', 10)
            ->has('courses.data.0.cover_url')
            ->has('courses.data.0.units_count')
        );

    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}&page=2")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 2)
            ->where('courses.meta.page', 2)
            ->where('courses.meta.from', 7)
            ->where('courses.meta.to', 8)
        );

    // Search filter behaves case-insensitively with %term% matching (like/ilike)
    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}&q=vacantes")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.name', 'Z - Con vacantes disponibles')
        );

    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}&q=VACANTES")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.name', 'Z - Con vacantes disponibles')
        );
});

it('enrolls a student in a course offering, decrements available spots and updates the real-time schedule', function () {
    $studentId = createActiveStudentForEnrollment();
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['name' => 'Física I', 'status' => 'published']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'group_code' => 'Grupo Único',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 2,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offering->teachers()->sync([$teacher->id]);
    $offering->sessions()->create([
        'title' => 'Cinemática',
        'session_date' => '2026-11-10',
        'starts_at' => '10:00',
        'ends_at' => '12:00',
        'teacher_id' => $teacher->id,
        'meeting_url' => 'https://meet.example.com/fisica',
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $studentId,
        'course_offering_id' => $offering->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $studentId,
        'course_id' => $course->id,
        'course_offering_id' => $offering->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('courses.data.0.is_enrolled', true)
            ->where('courses.data.0.offerings.0.available_spots', 1)
            ->has('studentEnrollments', 1)
            ->where('studentEnrollments.0.group_code', 'Grupo Único')
            ->has('calendarBlocks', 1)
            ->where('calendarBlocks.0.sessionDate', '2026-11-10')
            ->where('calendarBlocks.0.startsAt', '10:00')
        );
});

it('rejects enrollment when the course offering has 0 available spots or is not enrollable', function () {
    $student1 = createActiveStudentForEnrollment('Estudiante 1');
    $student2 = createActiveStudentForEnrollment('Estudiante 2');
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['status' => 'published']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'group_code' => 'Grupo Cupo 1',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 1,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offering->teachers()->sync([$teacher->id]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $student1,
        'course_offering_id' => $offering->id,
    ])->assertRedirect();

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $student2,
        'course_offering_id' => $offering->id,
    ])->assertSessionHasErrors('course_offering_id');

    $draftOffering = CourseOffering::factory()->create([
        'course_id' => Course::factory()->create(['status' => 'published'])->id,
        'group_code' => 'Grupo Borrador',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 10,
        'modality' => 'virtual',
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $student2,
        'course_offering_id' => $draftOffering->id,
    ])->assertSessionHasErrors('course_offering_id');
});

it('rejects enrollment when a session overlaps in date and time with another enrolled course, but allows consecutive or cancelled sessions', function () {
    $studentId = createActiveStudentForEnrollment();
    $teacher = userWithRole(Role::Docente);

    $courseA = Course::factory()->create(['name' => 'Química General', 'status' => 'published']);
    $offeringA = CourseOffering::factory()->create([
        'course_id' => $courseA->id,
        'group_code' => 'Grupo A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 20,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offeringA->teachers()->sync([$teacher->id]);
    $offeringA->sessions()->create([
        'title' => 'Enlaces químicos',
        'session_date' => '2026-11-12',
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'teacher_id' => $teacher->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $studentId,
        'course_offering_id' => $offeringA->id,
    ])->assertRedirect();

    $courseOverlap = Course::factory()->create(['name' => 'Álgebra Lineal', 'status' => 'published']);
    $offeringOverlap = CourseOffering::factory()->create([
        'course_id' => $courseOverlap->id,
        'group_code' => 'Grupo Solapado',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 20,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offeringOverlap->teachers()->sync([$teacher->id]);
    $offeringOverlap->sessions()->create([
        'title' => 'Matrices',
        'session_date' => '2026-11-12',
        'starts_at' => '09:00',
        'ends_at' => '11:00',
        'teacher_id' => $teacher->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $studentId,
        'course_offering_id' => $offeringOverlap->id,
    ])->assertSessionHasErrors('course_offering_id');

    // Consecutive session (10:00 - 12:00) + cancelled overlapping session (08:00 - 10:00) should be allowed
    $courseConsecutive = Course::factory()->create(['name' => 'Estadística', 'status' => 'published']);
    $offeringConsecutive = CourseOffering::factory()->create([
        'course_id' => $courseConsecutive->id,
        'group_code' => 'Grupo Consecutivo',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 20,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offeringConsecutive->teachers()->sync([$teacher->id]);
    $offeringConsecutive->sessions()->createMany([
        [
            'title' => 'Probabilidad (Cancelada)',
            'session_date' => '2026-11-12',
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'teacher_id' => $teacher->id,
            'status' => 'cancelled',
        ],
        [
            'title' => 'Distribuciones',
            'session_date' => '2026-11-12',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'teacher_id' => $teacher->id,
            'status' => 'scheduled',
        ],
    ]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $studentId,
        'course_offering_id' => $offeringConsecutive->id,
    ])->assertRedirect();

    expect(DB::table('enrollments')->where('student_id', $studentId)->where('status', 'active')->count())->toBe(2);
});

it('unenrolls a student, frees the spot immediately, preserves lesson progress and allows re-enrolling later', function () {
    $studentId = createActiveStudentForEnrollment();
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['name' => 'Programación Web', 'status' => 'published']);
    $unit = Unit::factory()->for($course)->create(['position' => 1]);
    $lesson = Lesson::factory()->for($unit)->create(['position' => 1]);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'group_code' => 'Grupo A',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 1,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offering->teachers()->sync([$teacher->id]);
    $offering->sessions()->create([
        'title' => 'HTML y CSS',
        'session_date' => '2026-11-15',
        'starts_at' => '14:00',
        'ends_at' => '16:00',
        'teacher_id' => $teacher->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $studentId,
        'course_offering_id' => $offering->id,
    ])->assertRedirect();

    $enrollmentId = (int) DB::table('enrollments')
        ->where('student_id', $studentId)
        ->where('course_id', $course->id)
        ->value('id');

    DB::table('lesson_progress')->insert([
        'student_id' => $studentId,
        'lesson_id' => $lesson->id,
        'completed_at' => now(),
    ]);

    $this->actingAs($this->admin)->delete("/enrollments/{$enrollmentId}")
        ->assertRedirect();

    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollmentId,
        'status' => 'cancelled',
    ]);
    $this->assertDatabaseHas('lesson_progress', [
        'student_id' => $studentId,
        'lesson_id' => $lesson->id,
    ]);
    $this->assertDatabaseHas('audit_logs', ['event' => 'enrollment.cancelled']);

    $this->actingAs($this->admin)->get("/enrollments?student_id={$studentId}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('courses.data.0.is_enrolled', false)
            ->where('courses.data.0.offerings.0.available_spots', 1)
            ->has('studentEnrollments', 0)
            ->has('calendarBlocks', 0)
        );

    // Re-enroll in another group of the same course
    $offeringB = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'group_code' => 'Grupo B',
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-12-15',
        'max_students' => 5,
        'modality' => 'virtual',
        'status' => 'published',
    ]);
    $offeringB->teachers()->sync([$teacher->id]);

    $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $studentId,
        'course_offering_id' => $offeringB->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollmentId,
        'student_id' => $studentId,
        'course_id' => $course->id,
        'course_offering_id' => $offeringB->id,
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('lesson_progress', [
        'student_id' => $studentId,
        'lesson_id' => $lesson->id,
    ]);
});
