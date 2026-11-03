<?php

namespace App\Modules\Courses\Domain;

final readonly class Course
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $objectives,
        public CourseStatus $status,
    ) {}
}
