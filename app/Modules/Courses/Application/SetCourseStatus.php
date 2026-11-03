<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Courses\Domain\CourseStatus;

class SetCourseStatus
{
    public function __construct(private readonly CourseRepository $courses) {}

    public function __invoke(int $courseId, CourseStatus $status): void
    {
        $this->courses->setCourseStatus($courseId, $status);
    }
}
