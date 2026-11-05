<?php

namespace App\Modules\Progress\Domain;

use InvalidArgumentException;

final readonly class CourseProgress
{
    public function __construct(
        public int $completedLessons,
        public int $totalLessons,
    ) {
        if ($completedLessons < 0 || $totalLessons < 0 || $completedLessons > $totalLessons) {
            throw new InvalidArgumentException('El conteo de lecciones completadas no es válido.');
        }
    }

    public function percentage(): int
    {
        if ($this->totalLessons === 0) {
            return 0;
        }

        return (int) round($this->completedLessons * 100 / $this->totalLessons);
    }

    public function status(): string
    {
        $percentage = $this->percentage();

        if ($percentage >= 100) {
            return 'completed';
        }

        if ($percentage > 0) {
            return 'in_progress';
        }

        return 'not_started';
    }

    /** @return array{completed_lessons: int, total_lessons: int, percentage: int, status: string} */
    public function toArray(): array
    {
        return [
            'completed_lessons' => $this->completedLessons,
            'total_lessons' => $this->totalLessons,
            'percentage' => $this->percentage(),
            'status' => $this->status(),
        ];
    }
}
