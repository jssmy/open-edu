<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\CourseMediaImage;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores supported course images and serves them with safe headers', function (
    string $filename,
    string $mimeType,
) {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();

    $upload = $this->actingAs($manager)->postJson("/courses/{$course->id}/content-images", [
        'file' => UploadedFile::fake()->image($filename),
    ])->assertCreated()
        ->assertJsonStructure(['id', 'src']);

    $image = CourseMediaImage::query()->sole();
    Storage::disk('local')->assertExists($image->storage_path);

    $this->get($upload->json('src'))
        ->assertOk()
        ->assertHeader('content-type', $mimeType)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline');
})->with([
    'jpeg' => ['lesson.jpg', 'image/jpeg'],
    'png' => ['lesson.png', 'image/png'],
    'webp' => ['lesson.webp', 'image/webp'],
]);

it('rejects unsupported image formats and uploads over five megabytes', function (
    UploadedFile $file,
) {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();

    $this->actingAs($manager)->postJson("/courses/{$course->id}/content-images", [
        'file' => $file,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    $this->assertDatabaseCount('course_media_images', 0);
    Storage::disk('local')->assertDirectoryEmpty('courses/'.$course->id.'/content-images');
})->with([
    'unsupported format' => fn () => UploadedFile::fake()->create('payload.gif', 10, 'image/gif'),
    'oversized image' => fn () => UploadedFile::fake()->image('large.jpg')->size(5121),
]);

it('accepts a course image at the five megabyte limit', function () {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();

    $this->actingAs($manager)->postJson("/courses/{$course->id}/content-images", [
        'file' => UploadedFile::fake()->image('maximum.png')->size(5120),
    ])->assertCreated();

    $this->assertDatabaseCount('course_media_images', 1);
});

it('allows an assigned teacher to upload and view a course image', function () {
    Storage::fake('local');
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher);

    $response = $this->actingAs($teacher)->postJson("/courses/{$course->id}/content-images", [
        'file' => UploadedFile::fake()->image('lesson.png'),
    ])->assertCreated();

    $this->get($response->json('src'))->assertOk()
        ->assertHeader('content-type', 'image/png');
});

it('hides course images from students without an active published enrollment', function () {
    Storage::fake('local');
    $course = Course::factory()->create(['status' => 'published']);
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);
    $image = CourseMediaImage::query()->create([
        'id' => (string) str()->uuid(),
        'course_id' => $course->id,
        'storage_path' => 'courses/'.$course->id.'/content-images/image.webp',
        'mime_type' => 'image/webp',
        'size_bytes' => 12,
        'uploaded_by' => userWithRole(Role::GestorDeCursos)->id,
    ]);
    Storage::disk('local')->put($image->storage_path, 'image bytes');

    $this->actingAs($studentUser)->get("/courses/{$course->id}/content-images/{$image->id}")
        ->assertNotFound();

    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($studentUser)->get("/courses/{$course->id}/content-images/{$image->id}")
        ->assertOk()
        ->assertHeader('content-type', 'image/webp');
});

it('hides course images for suspended enrollments and unpublished courses', function (
    string $courseStatus,
    EnrollmentStatus $enrollmentStatus,
) {
    Storage::fake('local');
    $course = Course::factory()->create(['status' => $courseStatus]);
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);
    $manager = userWithRole(Role::GestorDeCursos);
    $image = CourseMediaImage::query()->create([
        'id' => (string) str()->uuid(),
        'course_id' => $course->id,
        'storage_path' => 'courses/'.$course->id.'/content-images/private.webp',
        'mime_type' => 'image/webp',
        'size_bytes' => 12,
        'uploaded_by' => $manager->id,
    ]);
    Storage::disk('local')->put($image->storage_path, 'image bytes');
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $enrollmentStatus,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($studentUser)
        ->get("/courses/{$course->id}/content-images/{$image->id}")
        ->assertNotFound();
})->with([
    'suspended enrollment' => ['published', EnrollmentStatus::Suspended],
    'unpublished course' => ['draft', EnrollmentStatus::Active],
]);

it('forbids an unassigned teacher from uploading a course image', function () {
    Storage::fake('local');
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();

    $this->actingAs($teacher)->postJson("/courses/{$course->id}/content-images", [
        'file' => UploadedFile::fake()->image('lesson.png'),
    ])->assertNotFound();

    $this->assertDatabaseCount('course_media_images', 0);
});
