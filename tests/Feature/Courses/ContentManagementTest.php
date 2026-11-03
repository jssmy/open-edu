<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates units and text lessons', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);

    $this->actingAs($manager)->post("/courses/{$course->id}/units", [
        'title' => 'Unidad 1',
        'position' => 1,
    ])->assertRedirect();

    $unit = Unit::query()->where('course_id', $course->id)->firstOrFail();
    $this->actingAs($manager)->post("/units/{$unit->id}/lessons", [
        'title' => 'Introducción',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido de introducción',
    ])->assertRedirect();

    $this->assertDatabaseHas('lessons', [
        'unit_id' => $unit->id,
        'title' => 'Introducción',
        'content_type' => 'text',
        'content' => 'Contenido de introducción',
    ]);
});

it('persists the requested order of course units', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $first = Unit::query()->create(['course_id' => $course->id, 'title' => 'Primera', 'position' => 1]);
    $second = Unit::query()->create(['course_id' => $course->id, 'title' => 'Segunda', 'position' => 2]);

    $this->actingAs($manager)->patch('/units/reorder', [
        'unit_ids' => [$second->id, $first->id],
    ])->assertRedirect();

    expect($second->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2);
});

it('persists the requested order of lessons within a unit', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);
    $first = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Primera',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);
    $second = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Segunda',
        'position' => 2,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);

    $this->actingAs($manager)->patch('/lessons/reorder', [
        'lesson_ids' => [$second->id, $first->id],
    ])->assertRedirect();

    expect($second->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2);
});

it('archives a lesson and retains it in storage', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);
    $lesson = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Lección',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);

    $this->actingAs($manager)->patch("/lessons/{$lesson->id}/archive")->assertRedirect();

    expect($lesson->fresh()->is_archived)->toBeTrue();
});

it('rejects deleting a lesson that has recorded progress', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::query()->create([
        'name' => 'Biología',
        'description' => 'Descripción',
        'objectives' => 'Objetivos',
        'status' => 'draft',
    ]);
    $unit = Unit::query()->create(['course_id' => $course->id, 'title' => 'Unidad 1', 'position' => 1]);
    $lesson = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Lección',
        'position' => 1,
        'content_type' => 'text',
        'content' => 'Contenido',
    ]);
    $student = Student::factory()->create();
    if (! Schema::hasTable('lesson_progress')) {
        Schema::create('lesson_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unique(['student_id', 'lesson_id']);
        });
    }
    DB::table('lesson_progress')->insert([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'completed_at' => now(),
    ]);

    $this->actingAs($manager)->delete("/lessons/{$lesson->id}")
        ->assertSessionHasErrors(['general' => 'Las lecciones con progreso solo se pueden archivar.']);

    $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    $this->assertDatabaseHas('lesson_progress', ['lesson_id' => $lesson->id]);
});
