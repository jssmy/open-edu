<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('stores an accepted document upload for a lesson', function () {
    Storage::fake('local');
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);

    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Guía PDF',
        'position' => 1,
        'content_type' => 'document',
        'file' => UploadedFile::fake()->createWithContent('guia.pdf', 'contenido PDF'),
    ])->assertRedirect();

    $path = DB::table('lessons')->value('content');
    expect($path)->toBeString();
    Storage::disk('local')->assertExists($path);
});

it('rejects document uploads with unsupported formats', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);

    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Archivo inválido',
        'position' => 1,
        'content_type' => 'document',
        'file' => UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
    ])->assertSessionHasErrors('file');
});

it('rejects document uploads larger than the configured maximum', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);

    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Archivo grande',
        'position' => 1,
        'content_type' => 'document',
        'file' => UploadedFile::fake()->create('guia.pdf', 10241, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});
