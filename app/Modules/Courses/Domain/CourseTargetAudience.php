<?php

namespace App\Modules\Courses\Domain;

enum CourseTargetAudience: string
{
    case Student = 'student';
    case Teacher = 'teacher';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Estudiante',
            self::Teacher => 'Docente',
        };
    }
}
