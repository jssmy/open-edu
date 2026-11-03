<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

class AssignTeachers
{
    public function __construct(private readonly CourseRepository $courses) {}

    /** @param list<int> $teacherIds */
    public function __invoke(int $courseId, array $teacherIds): void
    {
        $this->courses->assignTeachers($courseId, $teacherIds);
    }
}
