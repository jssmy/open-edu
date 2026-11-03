<?php

namespace App\Modules\Courses\Domain;

final readonly class Unit
{
    public function __construct(
        public int $id,
        public int $courseId,
        public string $title,
        public int $position,
    ) {}
}
