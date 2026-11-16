<?php

use App\Models\User;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Progress\Infrastructure\Models\Certificate;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use Database\Seeders\CourseCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TeacherCoursesSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CourseCategorySeeder::class);
});

test('seeder creates the 8 default course categories idempotently with their assigned role_name', function () {
    $this->seed(CourseCategorySeeder::class);

    $expectedRolesByCategory = [
        'Onboarding' => 'docente',
        'Capacitación' => 'docente',
        'Disciplina' => 'estudiante',
        'Expresión' => 'estudiante',
        'Creatividad' => 'estudiante',
        'Lógica' => 'estudiante',
        'Idiomas' => 'estudiante',
        'Digital' => 'estudiante',
    ];

    expect(CourseCategory::query()->count())->toBe(8);

    foreach ($expectedRolesByCategory as $name => $expectedRole) {
        $category = CourseCategory::query()->where('name', $name)->first();
        expect($category)->not->toBeNull()
            ->and($category->role_name)->toBe($expectedRole);
    }
});

test('teacher courses seeder creates published teacher courses in Onboarding and Capacitación categories with units, lessons, and assessments', function () {
    $this->seed(TeacherCoursesSeeder::class);
    $this->seed(TeacherCoursesSeeder::class);

    $onboardingCat = CourseCategory::query()->where('slug', 'onboarding')->firstOrFail();
    $capacitacionCat = CourseCategory::query()->where('slug', 'capacitacion')->firstOrFail();

    $teacherCourses = Course::query()
        ->whereIn('category_id', [$onboardingCat->id, $capacitacionCat->id])
        ->where('status', 'published')
        ->withCount('units')
        ->get();

    expect($teacherCourses)->toHaveCount(6)
        ->and($teacherCourses->where('category_id', $onboardingCat->id))->toHaveCount(3)
        ->and($teacherCourses->where('category_id', $capacitacionCat->id))->toHaveCount(3);

    foreach ($teacherCourses as $course) {
        expect($course->target_audience)->toBe('teacher')
            ->and($course->issues_certificate)->toBeTrue()
            ->and($course->units_count)->toBeGreaterThan(0);
    }
});

test('administrator can create a new course category with default estudiante role or explicit role and duplicate names are rejected', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('administrador');

    // Default role_name is estudiante when omitted
    $this->actingAs($admin)
        ->post('/course-categories', ['name' => 'Robótica Educativa'])
        ->assertRedirect();

    $defaultCategory = CourseCategory::query()->where('name', 'Robótica Educativa')->firstOrFail();
    expect($defaultCategory->role_name)->toBe('estudiante');

    // Explicit docente role_name
    $this->actingAs($admin)
        ->post('/course-categories', [
            'name' => 'Mentoría Pedagógica',
            'role_name' => 'docente',
        ])
        ->assertRedirect();

    $teacherCategory = CourseCategory::query()->where('name', 'Mentoría Pedagógica')->firstOrFail();
    expect($teacherCategory->role_name)->toBe('docente');

    // Duplicate case-insensitive name rejected
    $this->actingAs($admin)
        ->post('/course-categories', ['name' => 'robótica educativa'])
        ->assertSessionHasErrors('name');
});

test('administrator creates a course selecting only category_id and issues_certificate defaults to true while audience derives from category role', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('administrador');

    $teacherCategory = CourseCategory::query()->where('name', 'Onboarding')->firstOrFail();
    $studentCategory = CourseCategory::query()->where('name', 'Lógica')->firstOrFail();

    $this->actingAs($admin)
        ->post('/courses', [
            'name' => 'Inducción Institucional Docente',
            'category_id' => $teacherCategory->id,
        ])
        ->assertRedirect();

    $teacherCourse = Course::query()->where('name', 'Inducción Institucional Docente')->firstOrFail();

    expect($teacherCourse->category_id)->toBe($teacherCategory->id)
        ->and($teacherCourse->target_audience)->toBe('teacher')
        ->and($teacherCourse->effectiveRoleName())->toBe('docente')
        ->and($teacherCourse->issues_certificate)->toBeTrue();

    $this->actingAs($admin)
        ->post('/courses', [
            'name' => 'Razonamiento Matemático I',
            'category_id' => $studentCategory->id,
        ])
        ->assertRedirect();

    $studentCourse = Course::query()->where('name', 'Razonamiento Matemático I')->firstOrFail();

    expect($studentCourse->category_id)->toBe($studentCategory->id)
        ->and($studentCourse->target_audience)->toBe('student')
        ->and($studentCourse->effectiveRoleName())->toBe('estudiante')
        ->and($studentCourse->issues_certificate)->toBeTrue();
});

test('scheduling and enrollments only show and allow courses from student categories', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('administrador');

    $teacher = User::factory()->create(['is_active' => true]);
    $teacher->assignRole('docente');

    $classroom = Classroom::query()->create([
        'name' => 'Aula 101',
        'capacity' => 30,
        'is_active' => true,
    ]);

    $studentCategory = CourseCategory::query()->where('name', 'Disciplina')->firstOrFail();
    $teacherCategory = CourseCategory::query()->where('name', 'Capacitación')->firstOrFail();

    $studentCourse = Course::factory()->published()->create([
        'name' => 'Curso de Disciplina Escolar',
        'category_id' => $studentCategory->id,
        'target_audience' => 'student',
    ]);

    $teacherCourse = Course::factory()->published()->create([
        'name' => 'Curso de Capacitación Docente',
        'category_id' => $teacherCategory->id,
        'target_audience' => 'teacher',
    ]);

    // 1. /scheduling only lists student-category courses in its selector
    $this->actingAs($admin)
        ->get('/scheduling')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Scheduling/Index')
            ->has('courses', 1)
            ->where('courses.0.id', $studentCourse->id)
        );

    // 2. Attempting to create a schedule for a teacher-category course is rejected
    $this->actingAs($admin)
        ->from('/scheduling')
        ->post('/scheduling/offerings', [
            'course_id' => $teacherCourse->id,
            'modality' => 'virtual',
            'teacher_id' => $teacher->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'max_students' => 25,
            'status' => 'published',
            'sessions' => [
                [
                    'title' => 'Sesión 1',
                    'session_date' => now()->addDay()->toDateString(),
                    'starts_at' => '09:00',
                    'ends_at' => '11:00',
                    'teacher_id' => $teacher->id,
                    'classroom_id' => $classroom->id,
                    'status' => 'scheduled',
                ],
            ],
        ])
        ->assertSessionHasErrors('course_id');

    // 3. /enrollments only lists student-category courses
    $this->actingAs($admin)
        ->get('/enrollments')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Enrollments/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $studentCourse->id)
        );
});

test('teacher sees all published courses of their role categories in /courses and can self-enroll in one click', function () {
    $teacher = User::factory()->create(['is_active' => true, 'name' => 'Prof. Carlos']);
    $teacher->assignRole('docente');

    $onboardingCat = CourseCategory::query()->where('name', 'Onboarding')->firstOrFail();
    $capacitacionCat = CourseCategory::query()->where('name', 'Capacitación')->firstOrFail();
    $studentCat = CourseCategory::query()->where('name', 'Idiomas')->firstOrFail();

    $onboardingCourse = Course::factory()->published()->create([
        'name' => 'Bienvenida Docente 2026',
        'category_id' => $onboardingCat->id,
        'target_audience' => 'teacher',
        'issues_certificate' => true,
    ]);

    $capacitacionCourse = Course::factory()->published()->create([
        'name' => 'Pedagogía Digital Avanzada',
        'category_id' => $capacitacionCat->id,
        'target_audience' => 'teacher',
        'issues_certificate' => true,
    ]);

    Course::factory()->create([
        'name' => 'Curso Docente en Borrador',
        'category_id' => $capacitacionCat->id,
        'target_audience' => 'teacher',
        'status' => 'draft',
    ]);

    $studentCourse = Course::factory()->published()->create([
        'name' => 'Inglés Básico Escolar',
        'category_id' => $studentCat->id,
        'target_audience' => 'student',
    ]);

    // Teacher visits /courses and sees BOTH published teacher-category courses (even before enrolling), and NO student or draft courses
    $this->actingAs($teacher)
        ->get('/courses')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('isRoleCatalogView', true)
            ->has('courses.data', 2)
            ->has('categories', 2)
        );

    // Teacher self-enrolls in $capacitacionCourse via POST /courses/{course}/enroll
    $this->actingAs($teacher)
        ->post("/courses/{$capacitacionCourse->id}/enroll")
        ->assertRedirect("/my/courses/{$capacitacionCourse->id}");

    $enrollment = Enrollment::query()
        ->where('user_id', $teacher->id)
        ->where('course_id', $capacitacionCourse->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->student_id)->toBeNull()
        ->and($enrollment->course_offering_id)->toBeNull()
        ->and($enrollment->status->value)->toBe('active');

    // Teacher cannot self-enroll in a student-category course
    $this->actingAs($teacher)
        ->from('/courses')
        ->post("/courses/{$studentCourse->id}/enroll")
        ->assertSessionHasErrors('course_id');
});

test('self-enrolled teacher can study asynchronously, complete lessons, pass optional assessment, and generate PDF certificate stored in volume with only file_url in DB', function () {
    Storage::fake('local');

    $teacher = User::factory()->create(['is_active' => true, 'name' => 'Prof. Elena Martínez']);
    $teacher->assignRole('docente');

    $category = CourseCategory::query()->where('name', 'Capacitación')->firstOrFail();

    $course = Course::factory()->published()->create([
        'name' => 'Transformación Digital en el Aula',
        'category_id' => $category->id,
        'target_audience' => 'teacher',
        'issues_certificate' => true,
    ]);

    $unit = Unit::query()->create([
        'course_id' => $course->id,
        'title' => 'Módulo Único',
        'position' => 1,
    ]);

    $lesson = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Herramientas Colaborativas',
        'position' => 1,
        'content_type' => 'link',
        'content' => 'https://example.com/recurso',
        'is_archived' => false,
    ]);

    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación Final Docente',
        'instructions' => null,
        'max_attempts' => 2,
        'passing_score' => 10,
        'total_score' => 10,
        'is_published' => true,
    ]);

    $question = $assessment->questions()->create([
        'statement' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Pregunta 1']]]]],
        'points' => 10,
        'position' => 1,
    ]);
    $correctChoice = $question->choices()->create(['text' => 'Correcta', 'is_correct' => true]);
    $question->choices()->create(['text' => 'Incorrecta', 'is_correct' => false]);

    // Teacher self-enrolls from /courses
    $this->actingAs($teacher)
        ->post("/courses/{$course->id}/enroll")
        ->assertRedirect("/my/courses/{$course->id}");

    // Cannot claim certificate before completing lesson and passing assessment
    $this->actingAs($teacher)
        ->from("/my/courses/{$course->id}")
        ->post("/courses/{$course->id}/certificate")
        ->assertSessionHasErrors('certificate');

    // Complete lesson as teacher
    $this->actingAs($teacher)
        ->post("/lessons/{$lesson->id}/complete")
        ->assertRedirect("/my/courses/{$course->id}");

    // Verify /courses returns progress percentage (100%) on the enrolled course card
    $this->actingAs($teacher)
        ->get('/courses')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.data.0.is_enrolled', true)
            ->where('courses.data.0.progress.completed_lessons', 1)
            ->where('courses.data.0.progress.total_lessons', 1)
            ->where('courses.data.0.progress.percentage', 100)
        );

    // Still cannot claim certificate before passing published assessment
    $this->actingAs($teacher)
        ->from("/my/courses/{$course->id}")
        ->post("/courses/{$course->id}/certificate")
        ->assertSessionHasErrors('certificate');

    // Take and submit assessment as enrolled teacher
    $this->actingAs($teacher)
        ->get("/assessments/{$assessment->id}/take")
        ->assertOk();

    $this->actingAs($teacher)
        ->post("/assessments/{$assessment->id}/attempts", [
            'answers' => [$question->id => $correctChoice->id],
        ])
        ->assertRedirect("/assessments/{$assessment->id}/results");

    // Now claim certificate
    $this->actingAs($teacher)
        ->post("/courses/{$course->id}/certificate")
        ->assertRedirect("/my/courses/{$course->id}");

    $certificate = Certificate::query()
        ->where('course_id', $course->id)
        ->where('user_id', $teacher->id)
        ->first();

    expect($certificate)->not->toBeNull()
        ->and($certificate->file_url)->toStartWith('/storage/certificates/CERT-')
        ->and($certificate->file_url)->toEndWith('.pdf');

    $storageRelativePath = "certificates/{$certificate->code}.pdf";
    Storage::disk('local')->assertExists($storageRelativePath);

    $pdfContent = Storage::disk('local')->get($storageRelativePath);
    expect($pdfContent)->toStartWith('%PDF-1.4')
        ->and($pdfContent)->toContain('ESPERANZA')
        ->and($pdfContent)->not->toContain('SELLO')
        ->and($pdfContent)->not->toContain('CATEGORIA')
        ->and($pdfContent)->not->toContain('AULA VIRTUAL');

    // Download certificate endpoint streams the PDF
    $this->actingAs($teacher)
        ->get("/certificates/{$certificate->id}/download")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('course with issues_certificate false does not issue certificate even at 100% completion', function () {
    Storage::fake('local');

    $studentUser = User::factory()->create(['is_active' => true]);
    $studentUser->assignRole('estudiante');
    $student = Student::factory()->create([
        'user_id' => $studentUser->id,
        'is_active' => true,
    ]);

    $course = Course::factory()->published()->create([
        'target_audience' => 'student',
        'issues_certificate' => false,
    ]);

    $unit = Unit::query()->create([
        'course_id' => $course->id,
        'title' => 'Unidad 1',
        'position' => 1,
    ]);

    $lesson = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Lección 1',
        'position' => 1,
        'content_type' => 'link',
        'content' => 'https://example.com',
        'is_archived' => false,
    ]);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'status' => 'published',
        'starts_on' => now()->subDay()->toDateString(),
        'ends_on' => now()->addMonth()->toDateString(),
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'user_id' => $studentUser->id,
        'course_id' => $course->id,
        'course_offering_id' => $offering->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $this->actingAs($studentUser)
        ->post("/lessons/{$lesson->id}/complete")
        ->assertRedirect();

    $this->actingAs($studentUser)
        ->from("/my/courses/{$course->id}")
        ->post("/courses/{$course->id}/certificate")
        ->assertSessionHasErrors('certificate');

    expect(Certificate::query()->count())->toBe(0);
});
