<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;

it('manages classrooms and prevents deleting or deactivating a classroom with active sessions', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);

    $this->actingAs($admin)->post('/scheduling/classrooms', [
        'name' => 'Aula 201',
        'capacity' => 35,
    ])->assertRedirect();

    $classroom = Classroom::query()->where('name', 'Aula 201')->firstOrFail();

    $offering = CourseOffering::factory()->create([
        'course_id' => Course::factory()->create()->id,
        'modality' => 'in_person',
    ]);
    $offering->teachers()->sync([$teacher->id]);
    CourseSession::query()->create([
        'course_offering_id' => $offering->id,
        'title' => 'Sesión presencial',
        'session_date' => '2026-11-20',
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($admin)->delete("/scheduling/classrooms/{$classroom->id}")
        ->assertSessionHasErrors('classroom');

    $this->actingAs($admin)->put("/scheduling/classrooms/{$classroom->id}", [
        'name' => 'Aula 201',
        'capacity' => 35,
        'is_active' => false,
    ])->assertSessionHasErrors('classroom');
});

it('confirms academic periods routes are removed', function () {
    $admin = userWithRole(Role::Administrador);

    $this->actingAs($admin)->get('/scheduling/periods')->assertNotFound();
});
