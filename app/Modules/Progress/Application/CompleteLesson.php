<?php

namespace App\Modules\Progress\Application;

use App\Modules\Progress\Domain\ProgressRepository;

final class CompleteLesson
{
    public function __construct(private readonly ProgressRepository $progress) {}

    public function __invoke(int $studentId, int $lessonId): void
    {
        $this->progress->markLessonCompleted($studentId, $lessonId);
    }
}
