<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;

$adminOnlyRoutes = [
    ['GET', '/users', []],
    ['POST', '/users', []],
    ['GET', '/users/999999', []],
    ['PUT', '/users/999999', []],
    ['PATCH', '/users/999999/status', ['is_active' => false]],
    ['GET', '/roles', []],
    ['PUT', '/roles/999999/permissions', ['permissions' => []]],
    ['GET', '/scheduling', []],
    ['POST', '/scheduling/schedules', []],
];

$courseManagementRoutes = [
    ['POST', '/courses', []],
    ['PUT', '/courses/{course}', ['name' => 'Curso', 'description' => '', 'objectives' => '']],
    ['DELETE', '/courses/{course}', []],
    ['PATCH', '/courses/{course}/status', ['status' => 'published']],
];

$courseContentRoutes = [
    ['POST', '/courses/{course}/units', ['title' => 'Unidad', 'position' => 1]],
    ['PATCH', '/units/reorder', ['unit_ids' => []]],
    ['PUT', '/units/{unit}', ['title' => 'Unidad', 'position' => 1]],
    ['DELETE', '/units/{unit}', []],
    ['POST', '/units/{unit}/lessons', []],
    ['PATCH', '/lessons/reorder', ['lesson_ids' => []]],
    ['PUT', '/lessons/{lesson}', []],
    ['DELETE', '/lessons/{lesson}', []],
    ['PATCH', '/lessons/{lesson}/archive', []],
];

$courseViewRoutes = [
    ['GET', '/courses', []],
    ['GET', '/courses/{course}', []],
];

$studentManagementRoutes = [
    ['GET', '/students', []],
    ['POST', '/students', []],
    ['PUT', '/students/999999', []],
    ['PATCH', '/students/999999/status', ['is_active' => false]],
];

$enrollmentManagementRoutes = [
    ['POST', '/courses/{courseId}/enrollments', []],
];

$routeAccessCases = [];
foreach ([
    [Role::GestorDeCursos, $adminOnlyRoutes],
    [Role::Docente, [...$adminOnlyRoutes, ...$courseManagementRoutes, ...array_slice($studentManagementRoutes, 1), ...$enrollmentManagementRoutes]],
    [Role::Estudiante, [
        ...$adminOnlyRoutes,
        ...$courseManagementRoutes,
        ...$courseContentRoutes,
        ...$courseViewRoutes,
        ...$studentManagementRoutes,
        ...$enrollmentManagementRoutes,
    ]],
] as [$role, $routes]) {
    foreach ($routes as [$method, $uri, $payload]) {
        $routeAccessCases[$role->value.' '.$method.' '.$uri] = [$role, $method, $uri, $payload];
    }
}

it('returns 403 when a role accesses an operation outside its route permissions', function (
    Role $role,
    string $method,
    string $uri,
    array $payload,
) {
    $user = userWithRole($role);

    if (str_contains($uri, '{course}') || str_contains($uri, '{courseId}') || str_contains($uri, '{unit}') || str_contains($uri, '{lesson}')) {
        $course = Course::factory()->create();
        $uri = str_replace('{course}', (string) $course->id, $uri);
        $uri = str_replace('{courseId}', (string) $course->id, $uri);

        if (str_contains($uri, '{unit}') || str_contains($uri, '{lesson}')) {
            $unit = Unit::factory()->for($course)->create();
            $uri = str_replace('{unit}', (string) $unit->id, $uri);

            if (str_contains($uri, '{lesson}')) {
                $lesson = Lesson::factory()->for($unit)->create();
                $uri = str_replace('{lesson}', (string) $lesson->id, $uri);
            }
        }
    }

    $this->actingAs($user);

    $response = match ($method) {
        'GET' => $this->get($uri),
        'POST' => $this->post($uri, $payload),
        'PUT' => $this->put($uri, $payload),
        'PATCH' => $this->patch($uri, $payload),
        'DELETE' => $this->delete($uri, $payload),
    };

    $response->assertForbidden();
})->with($routeAccessCases);
