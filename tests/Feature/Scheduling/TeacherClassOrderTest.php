<?php

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('teacher classes are sorted by closest start date ascending', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('docente');

    $course1 = Course::factory()->create(['name' => 'Curso Empieza en 10 días']);
    $course2 = Course::factory()->create(['name' => 'Curso Empieza Mañana']);
    $course3 = Course::factory()->create(['name' => 'Curso Pasado']);

    $offeringFar = CourseOffering::query()->create([
        'course_id' => $course1->id,
        'group_code' => 'FAR',
        'modality' => 'in_person',
        'starts_on' => now()->addDays(10)->toDateString(),
        'ends_on' => now()->addMonths(2)->toDateString(),
        'max_students' => 20,
        'status' => 'active',
    ]);
    $offeringFar->teachers()->attach($teacher->id);

    $offeringSoon = CourseOffering::query()->create([
        'course_id' => $course2->id,
        'group_code' => 'SOON',
        'modality' => 'virtual',
        'starts_on' => now()->addDay()->toDateString(),
        'ends_on' => now()->addMonths(2)->toDateString(),
        'max_students' => 20,
        'status' => 'active',
    ]);
    $offeringSoon->teachers()->attach($teacher->id);

    $offeringPast = CourseOffering::query()->create([
        'course_id' => $course3->id,
        'group_code' => 'PAST',
        'modality' => 'in_person',
        'starts_on' => now()->subDays(5)->toDateString(),
        'ends_on' => now()->addMonth()->toDateString(),
        'max_students' => 20,
        'status' => 'active',
    ]);
    $offeringPast->teachers()->attach($teacher->id);

    $this->actingAs($teacher)
        ->get('/my/classes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Classes')
            ->has('classes', 3)
            ->where('classes.0.group_code', 'SOON')
            ->where('classes.1.group_code', 'FAR')
            ->where('classes.2.group_code', 'PAST')
        );
});

