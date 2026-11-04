<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;

it('rejects overlapping teacher schedules even when virtual and allows consecutive blocks', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher = userWithRole(Role::Docente);
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00']],
    ])->assertRedirect();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '11:00']],
    ])->assertSessionHasErrors('teacher_id');

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher->id,
        'academic_period_id' => $period->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 1, 'starts_at' => '10:00', 'ends_at' => '12:00']],
    ])->assertRedirect();
});

it('rejects classroom conflicts in the same period but allows virtual or different periods', function () {
    $admin = userWithRole(Role::Administrador);
    $teacher1 = userWithRole(Role::Docente);
    $teacher2 = userWithRole(Role::Docente);
    $period1 = AcademicPeriod::factory()->create();
    $period2 = AcademicPeriod::factory()->create();
    $classroom = Classroom::factory()->create();

    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher1->id,
        'academic_period_id' => $period1->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
        'blocks' => [['day_of_week' => 2, 'starts_at' => '10:00', 'ends_at' => '12:00']],
    ])->assertRedirect();

    // Conflicto de aula mismo periodo
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher2->id,
        'academic_period_id' => $period1->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
        'blocks' => [['day_of_week' => 2, 'starts_at' => '11:00', 'ends_at' => '13:00']],
    ])->assertSessionHasErrors('classroom_id');

    // Virtual al mismo horario con otro docente: permitido
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher2->id,
        'academic_period_id' => $period1->id,
        'modality' => 'virtual',
        'blocks' => [['day_of_week' => 2, 'starts_at' => '10:00', 'ends_at' => '12:00']],
    ])->assertRedirect();

    // Misma aula en otro periodo: permitido
    $this->actingAs($admin)->post('/scheduling/schedules', [
        'course_id' => Course::factory()->create()->id,
        'teacher_id' => $teacher1->id,
        'academic_period_id' => $period2->id,
        'modality' => 'in_person',
        'classroom_id' => $classroom->id,
        'blocks' => [['day_of_week' => 2, 'starts_at' => '10:00', 'ends_at' => '12:00']],
    ])->assertRedirect();
});

