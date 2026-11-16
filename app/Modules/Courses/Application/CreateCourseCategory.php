<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

final class CreateCourseCategory
{
    public function __construct(private readonly CourseRepository $courses) {}

    /** @return array{id: int, name: string, slug: string, role_name: string} */
    public function __invoke(string $name, string $roleName = 'estudiante'): array
    {
        $normalizedRole = trim($roleName) !== '' ? trim($roleName) : 'estudiante';

        return $this->courses->createCategory(trim($name), $normalizedRole);
    }
}
