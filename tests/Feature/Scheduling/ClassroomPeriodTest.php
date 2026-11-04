<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;

it('manages classrooms and prevents deleting or deactivating a classroom with schedules', function () {
    $admin = userWithRole(Role::Administrador);

    $this->actingAs($admin)->post('/scheduling/classrooms', [
        'name' => 'Aula 201',
        'capacity' => 35,
    ])->assertRedirect();

    $classroom = Classroom::query()->where('name', 'Aula 201')->firstOrFail();

    CourseSchedule::factory()->create([
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => userWithRole(Role::Docente)->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
    ]);

    $this->actingAs($admin)->delete("/scheduling/classrooms/{$classroom->id}")
        ->assertSessionHasErrors('classroom');

    $this->actingAs($admin)->put("/scheduling/classrooms/{$classroom->id}", [
        'name' => 'Aula 201',
        'capacity' => 35,
        'is_active' => false,
    ])->assertSessionHasErrors('classroom');
});

it('manages academic periods and validates date order', function () {
    $admin = userWithRole(Role::Administrador);

    $this->actingAs($admin)->post('/scheduling/periods', [
        'name' => 'Ciclo 2027-I',
        'starts_on' => '2027-03-01',
        'ends_on' => '2027-02-01',
    ])->assertSessionHasErrors('ends_on');

    $this->actingAs($admin)->post('/scheduling/periods', [
        'name' => 'Ciclo 2027-I',
        'starts_on' => '2027-03-01',
        'ends_on' => '2027-07-15',
    ])->assertRedirect();

    $this->assertDatabaseHas('academic_periods', ['name' => 'Ciclo 2027-I']);
});

