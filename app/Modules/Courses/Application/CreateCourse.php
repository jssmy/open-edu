<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

class CreateCourse
{
    public function __construct(private readonly CourseRepository $courses) {}

    /** @param array{name: string, description: ?string, objectives: ?string} $attributes */
    public function __invoke(array $attributes): int
    {
        return $this->courses->createCourse($attributes);
    }
}
