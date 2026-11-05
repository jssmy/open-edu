<?php

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('my schedule returns calendarBlocks with courseId for each session', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('docente');

    $courseA = Course::factory()->create(['name' => 'Matemática I']);
    $courseB = Course::factory()->create(['name' => 'Física I']);

    $offeringA = CourseOffering::query()->create([
        'course_id' => $courseA->id,
        'group_code' => 'MAT-01',
        'modality' => 'in_person',
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->addMonths(3)->toDateString(),
        'max_students' => 30,
        'status' => 'active',
    ]);
    $offeringA->teachers()->attach($teacher->id);

    $offeringB = CourseOffering::query()->create([
        'course_id' => $courseB->id,
        'group_code' => 'FIS-01',
        'modality' => 'virtual',
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->addMonths(3)->toDateString(),
        'max_students' => 25,
        'status' => 'active',
    ]);
    $offeringB->teachers()->attach($teacher->id);

    CourseSession::query()->create([
        'course_offering_id' => $offeringA->id,
        'teacher_id' => $teacher->id,
        'title' => 'Clase Mat',
        'session_date' => now()->toDateString(),
        'starts_at' => '08:00',
        'ends_at' => '10:00',
        'status' => 'scheduled',
    ]);

    CourseSession::query()->create([
        'course_offering_id' => $offeringB->id,
        'teacher_id' => $teacher->id,
        'title' => 'Clase Fis',
        'session_date' => now()->toDateString(),
        'starts_at' => '10:30',
        'ends_at' => '12:30',
        'status' => 'scheduled',
    ]);

    $this->actingAs($teacher)
        ->get('/my/schedule')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Scheduling/MySchedule')
            ->has('calendarBlocks', 2)
            ->where('calendarBlocks', fn ($blocks) => collect($blocks)->pluck('courseId')->sort()->values()->all() === [$courseA->id, $courseB->id])
        );
});
