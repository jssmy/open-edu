<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use Illuminate\Http\UploadedFile;

it('stores an HTTPS video URL as external lesson content', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $unit = Unit::factory()->for(Course::factory())->create();

    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Clase grabada',
        'position' => 1,
        'content_type' => 'video',
        'content' => 'https://media.example.test/clase.mp4',
    ])->assertRedirect();

    expect(Lesson::query()->sole()->content)->toBe('https://media.example.test/clase.mp4');
});

it('rejects HTTP URLs for video lessons', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $unit = Unit::factory()->for(Course::factory())->create();

    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Clase insegura',
        'position' => 1,
        'content_type' => 'video',
        'content' => 'http://media.example.test/clase.mp4',
    ])->assertSessionHasErrors('content');

    $this->assertDatabaseCount('lessons', 0);
});

it('rejects uploaded video files', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $unit = Unit::factory()->for(Course::factory())->create();

    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Video subido',
        'position' => 1,
        'content_type' => 'video',
        'content' => 'https://media.example.test/clase.mp4',
        'file' => UploadedFile::fake()->createWithContent('clase.mp4', 'video bytes'),
    ])->assertSessionHasErrors('file');

    $this->assertDatabaseCount('lessons', 0);
});
