<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

class UpdateCourse
{
    public function __construct(private readonly CourseRepository $courses) {}

    /** @param array{name: string, description: ?string, objectives: ?string} $attributes */
    public function __invoke(int $courseId, array $attributes): void
    {
        $this->courses->updateCourse($courseId, $attributes);
    }
}
