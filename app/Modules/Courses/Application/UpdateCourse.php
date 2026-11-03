<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

class UpdateCourse
{
    public function __construct(private readonly CourseRepository $courses) {}

    /** @param array{name: string, description: array|null, objectives: array|null, intro_video_provider: string|null, intro_video_id: string|null} $attributes */
    public function __invoke(int $courseId, int $expectedVersion, array $attributes): void
    {
        $this->courses->updateCourse($courseId, $expectedVersion, $attributes);
    }
}
