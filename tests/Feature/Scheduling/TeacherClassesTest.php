<?php

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Inertia\Testing\AssertableInertia as Assert;

it('shows only assigned classes to the teacher', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $otherTeacher = userWithRole(Role::Docente, ['must_change_password' => false]);

    $course1 = Course::factory()->create(['name' => 'Curso de Física 1']);
    $course2 = Course::factory()->create(['name' => 'Curso de Química']);

    $offering1 = CourseOffering::factory()->create([
        'course_id' => $course1->id,
        'group_code' => 'FIS-01',
    ]);
    $offering1->teachers()->attach($teacher->id);

    $offering2 = CourseOffering::factory()->create([
        'course_id' => $course2->id,
        'group_code' => 'QUI-01',
    ]);
    $offering2->teachers()->attach($otherTeacher->id);

    $this->actingAs($teacher)->get('/my/classes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Teacher/Classes')
            ->has('classes', 1)
            ->where('classes.0.group_code', 'FIS-01')
            ->where('classes.0.course.title', 'Curso de Física 1')
        );
});

it('allows teacher to open slide viewer for assigned class', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $course = Course::factory()->create(['name' => 'Curso de Robótica']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    Lesson::factory()->create([
        'unit_id' => $unit->id,
        'position' => 1,
        'title' => 'Lección 1: Sensores',
        'content_type' => ContentType::Text->value,
    ]);

    $offering = CourseOffering::factory()->create(['course_id' => $course->id]);
    $offering->teachers()->attach($teacher->id);

    $this->actingAs($teacher)->get("/my/classes/{$offering->id}/viewer")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.id', $course->id)
            ->where('isTeacherView', true)
            ->where('backUrl', '/my/classes')
        );
});

it('forbids teacher from opening viewer for unassigned class', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $otherTeacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create(['course_id' => $course->id]);
    $offering->teachers()->attach($otherTeacher->id);

    $this->actingAs($teacher)->get("/my/classes/{$offering->id}/viewer")
        ->assertForbidden();
});
