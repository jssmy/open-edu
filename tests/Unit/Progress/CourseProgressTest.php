<?php

use App\Modules\Progress\Domain\CourseProgress;

it('calculates zero progress when there are no active lessons', function () {
    $progress = new CourseProgress(0, 0);

    expect($progress->percentage())->toBe(0);
});

it('calculates the rounded percentage of completed active lessons', function () {
    $progress = new CourseProgress(2, 3);

    expect($progress->percentage())->toBe(67);
});

it('rejects invalid lesson counts', function (int $completedLessons, int $totalLessons) {
    expect(fn () => new CourseProgress($completedLessons, $totalLessons))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'negative completed count' => [-1, 2],
    'negative total count' => [0, -1],
    'completed exceeds total' => [2, 1],
]);
