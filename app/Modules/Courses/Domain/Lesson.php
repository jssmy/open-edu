<?php

namespace App\Modules\Courses\Domain;

use App\Shared\Exceptions\DomainException;

final readonly class Lesson
{
    public function __construct(
        public int $id,
        public int $unitId,
        public string $title,
        public int $position,
        public ContentType $contentType,
        public ?string $content,
        public bool $isArchived,
    ) {}

    public function assertCanBeDeleted(bool $hasProgress): void
    {
        if ($hasProgress) {
            throw new DomainException('Las lecciones con progreso solo se pueden archivar.', 'general');
        }
    }
}
