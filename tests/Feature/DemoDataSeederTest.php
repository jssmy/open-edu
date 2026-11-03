<?php

use Database\Seeders\DemoDataSeeder;

it('seeds repeatable course, student, enrollment, and progress data', function () {
    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('students', 3);
    $this->assertDatabaseCount('courses', 2);
    $this->assertDatabaseCount('units', 2);
    $this->assertDatabaseCount('lessons', 4);
    $this->assertDatabaseCount('enrollments', 6);
    $this->assertDatabaseCount('lesson_progress', 2);
    $this->assertDatabaseHas('courses', [
        'name' => 'Introducción a OpenEdu',
        'status' => 'published',
    ]);
});
