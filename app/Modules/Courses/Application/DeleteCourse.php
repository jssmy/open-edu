<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

class DeleteCourse
{
    public function __construct(private readonly CourseRepository $courses) {}

    public function __invoke(int $courseId, bool $confirmImpact): void
    {
        $this->courses->deleteCourse($courseId, $confirmImpact);
    }
}
