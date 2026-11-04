<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('creates courses in draft status for an authorized course manager', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => 'Curso introductorio',
        'objectives' => 'Comprender los fundamentos de biología',
    ])->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'name' => 'Biología',
        'status' => 'draft',
    ]);
});

it('opens the new course page without resolving create as a course id', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->get('/courses/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Courses/Edit')
            ->where('course', null));
});

it('updates and shows a course', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Curso anterior',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $course->refresh();

    $this->actingAs($manager)->put("/courses/{$course->id}", [
        'name' => 'Curso actualizado',
        'description' => 'Nueva descripción',
        'objectives' => 'Nuevos objetivos',
        'lock_version' => $course->lock_version,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($course->fresh()->name)->toBe('Curso actualizado');
    $this->actingAs($manager)->get("/courses/{$course->id}")->assertOk();
});

it('publishes and deactivates a course', function (string $status) {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->patch("/courses/{$course->id}/status", [
        'status' => $status,
    ])->assertRedirect();

    expect($course->fresh()->status)->toBe($status);
})->with([
    'published',
    'deactivated',
]);

it('soft deletes a course without enrollments', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->delete("/courses/{$course->id}")->assertRedirect();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('shows assigned teachers in read-only mode on the course detail page', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $teacher = userWithRole(Role::Docente, ['name' => 'Elena Torres']);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $course->teachers()->attach($teacher->id);

    $this->actingAs($manager)->get("/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.teachers.0.name', 'Elena Torres')
            ->missing('availableTeachers'));
});

it('forbids students from managing courses', function () {
    $student = userWithRole(Role::Estudiante);

    $this->actingAs($student)->post('/courses', [])->assertForbidden();
});

it('paginates and filters the course list', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    foreach (range(1, 12) as $i) {
        Course::factory()->create(['name' => sprintf('Curso %02d', $i), 'status' => $i === 12 ? 'published' : 'draft']);
    }

    $this->actingAs($manager)->get('/courses')->assertInertia(fn ($page) => $page
        ->has('courses.data', 10)
        ->where('courses.meta.total', 12));

    $this->actingAs($manager)->get('/courses?q=Curso&status=published')->assertInertia(fn ($page) => $page
        ->has('courses.data', 1)
        ->where('courses.data.0.name', 'Curso 12')
        ->has('courses.data.0.excerpt')
        ->has('courses.data.0.units_count'));
});

it('stores an optional cover image and serves a generated default when missing', function () {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Con portada',
        'cover_image' => UploadedFile::fake()->image('portada.png', 640, 360),
    ])->assertSessionHasNoErrors();
    $withCover = Course::query()->where('name', 'Con portada')->firstOrFail();
    Storage::disk('local')->assertExists($withCover->cover_path);
    $this->actingAs($manager)->get("/courses/{$withCover->id}/cover")
        ->assertOk()->assertHeader('Content-Type', 'image/png');

    $this->actingAs($manager)->post('/courses', ['name' => 'Sin portada']);
    $plain = Course::query()->where('name', 'Sin portada')->firstOrFail();
    expect($plain->cover_path)->toBeNull();
    $response = $this->actingAs($manager)->get("/courses/{$plain->id}/cover")->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($response->getContent())->toContain('<svg')->toContain('>S</text>');

    $this->actingAs($manager)->get('/courses')->assertInertia(fn ($page) => $page
        ->where('courses.data.0.cover_url', fn ($url) => str_contains($url, "/courses/{$withCover->id}/cover")));
});

it('replaces and removes the cover image, deleting the old file', function () {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();

    $send = fn (array $extra) => $this->actingAs($manager)->put("/courses/{$course->id}", [
        'name' => $course->name, 'lock_version' => $course->fresh()->lock_version, ...$extra,
    ]);

    $send(['cover_image' => UploadedFile::fake()->image('a.jpg')])->assertSessionHasNoErrors();
    $first = $course->fresh()->cover_path;
    $send(['cover_image' => UploadedFile::fake()->image('b.webp')])->assertSessionHasNoErrors();
    $second = $course->fresh()->cover_path;
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($second);

    $send(['remove_cover' => true])->assertSessionHasNoErrors();
    expect($course->fresh()->cover_path)->toBeNull();
    Storage::disk('local')->assertMissing($second);
});

it('rejects covers that are not supported images', function () {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Mala portada',
        'cover_image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('cover_image');
    $this->actingAs($manager)->post('/courses', [
        'name' => 'Pesada',
        'cover_image' => UploadedFile::fake()->image('big.png')->size(6000),
    ])->assertSessionHasErrors('cover_image');
    $this->assertDatabaseCount('courses', 0);
});

it('keeps the cover file of a soft-deleted course but stops serving it', function () {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);
    $this->actingAs($manager)->post('/courses', [
        'name' => 'Eliminable',
        'cover_image' => UploadedFile::fake()->image('c.png'),
    ]);
    $course = Course::query()->where('name', 'Eliminable')->firstOrFail();

    $this->actingAs($manager)->delete("/courses/{$course->id}");

    Storage::disk('local')->assertExists($course->cover_path);
    $this->actingAs($manager)->get("/courses/{$course->id}/cover")->assertNotFound();
});
