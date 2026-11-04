<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Courses\Domain\FileStorage;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Str;

class SetCourseCover
{
    public function __construct(
        private readonly CourseRepository $courses,
        private readonly FileStorage $files,
    ) {}

    public function __invoke(int $courseId, ?string $temporaryPath, ?string $mimeType, bool $remove = false): void
    {
        if ($temporaryPath === null && ! $remove) {
            return;
        }

        $previous = $this->courses->coverPath($courseId);
        $path = null;

        if ($temporaryPath !== null) {
            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new DomainException('La portada debe ser una imagen JPEG, PNG o WebP válida.', 'cover_image'),
            };
            $path = $this->files->store("courses/{$courseId}/cover", $temporaryPath, Str::uuid().'.'.$extension);
        }

        $this->courses->setCoverPath($courseId, $path, $path === null ? null : $mimeType);

        if ($previous !== null) {
            $this->files->delete($previous);
        }
    }
}
