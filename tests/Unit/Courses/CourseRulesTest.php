<?php

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Domain\CourseStatus;
use App\Modules\Courses\Domain\Lesson;
use App\Shared\Exceptions\DomainException;

it('allows deleting lessons without progress', function () {
    $lesson = new Lesson(1, 1, 'Introducción', 1, ContentType::Text, 'Contenido', false);

    $lesson->assertCanBeDeleted(false);

    expect($lesson->isArchived)->toBeFalse();
});

it('requires archiving lessons that have progress instead of deleting them', function () {
    $lesson = new Lesson(1, 1, 'Introducción', 1, ContentType::Text, 'Contenido', false);

    expect(fn () => $lesson->assertCanBeDeleted(true))
        ->toThrow(DomainException::class, 'Las lecciones con progreso solo se pueden archivar.');
});

it('defines the supported course and content states', function () {
    expect(array_column(CourseStatus::cases(), 'value'))
        ->toBe(['draft', 'published', 'deactivated'])
        ->and(array_column(ContentType::cases(), 'value'))
        ->toBe(['text', 'document', 'image', 'link', 'video']);
});
