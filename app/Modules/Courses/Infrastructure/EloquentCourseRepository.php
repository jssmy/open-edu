<?php

namespace App\Modules\Courses\Infrastructure;

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Courses\Domain\CourseStatus;
use App\Modules\Courses\Domain\Lesson as LessonEntity;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Shared\Exceptions\DomainException;
use App\Shared\Exceptions\StaleContentVersionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class EloquentCourseRepository implements CourseRepository
{
    public function createCategory(string $name, string $roleName = 'estudiante'): array
    {
        $trimmed = trim($name);
        $slug = Str::slug($trimmed);
        $normalizedRole = trim($roleName) !== '' ? trim($roleName) : 'estudiante';

        if ($trimmed === '' || $slug === '') {
            throw new DomainException('Ingresa un nombre válido para la categoría.', 'name');
        }

        $exists = CourseCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->orWhere('slug', $slug)
            ->exists();

        if ($exists) {
            throw new DomainException('Ya existe una categoría con ese nombre.', 'name');
        }

        $category = CourseCategory::query()->create([
            'name' => $trimmed,
            'slug' => $slug,
            'role_name' => $normalizedRole,
            'is_active' => true,
        ]);

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'role_name' => $category->role_name,
        ];
    }

    public function createCourse(array $attributes): int
    {
        $categoryId = $attributes['category_id'] ?? null;
        $derivedAudience = $this->resolveTargetAudience($categoryId, $attributes['target_audience'] ?? null);

        $payload = [
            'issues_certificate' => true,
            ...$attributes,
            'target_audience' => $derivedAudience,
        ];

        return Course::query()->create($payload)->id;
    }

    public function updateCourse(int $courseId, int $expectedVersion, array $attributes): void
    {
        if (array_key_exists('category_id', $attributes)) {
            $attributes['target_audience'] = $this->resolveTargetAudience(
                $attributes['category_id'],
                $attributes['target_audience'] ?? null,
            );
        }

        $updated = Course::query()
            ->whereKey($courseId)
            ->where('lock_version', $expectedVersion)
            ->update([...$attributes, 'lock_version' => DB::raw('lock_version + 1')]);

        if ($updated !== 1) {
            throw new StaleContentVersionException(
                'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.',
            );
        }
    }

    private function resolveTargetAudience(?int $categoryId, ?string $fallbackAudience): string
    {
        if ($categoryId !== null) {
            $roleName = CourseCategory::query()->whereKey($categoryId)->value('role_name');
            if (is_string($roleName) && $roleName !== '') {
                return $roleName === 'estudiante' ? 'student' : 'teacher';
            }
        }

        return is_string($fallbackAudience) && $fallbackAudience !== ''
            ? $fallbackAudience
            : 'student';
    }

    public function coverPath(int $courseId): ?string
    {
        return Course::query()->whereKey($courseId)->value('cover_path');
    }

    public function setCoverPath(int $courseId, ?string $path, ?string $mimeType): void
    {
        Course::query()->whereKey($courseId)->update(['cover_path' => $path, 'cover_mime' => $mimeType]);
    }

    public function setCourseStatus(int $courseId, CourseStatus $status): void
    {
        Course::query()->findOrFail($courseId)->update([
            'status' => $status->value,
            'lock_version' => DB::raw('lock_version + 1'),
        ]);
    }

    public function isPublished(int $courseId): bool
    {
        return Course::query()->whereKey($courseId)->where('status', CourseStatus::Published->value)->exists();
    }

    public function deleteCourse(int $courseId, bool $confirmImpact): void
    {
        if (Schema::hasTable('enrollments')
            && DB::table('enrollments')->where('course_id', $courseId)->exists()
            && ! $confirmImpact) {
            throw new DomainException(
                'Este curso tiene inscripciones. Confirma la eliminación para conservar su historial.',
                'confirm_impact',
            );
        }

        Course::query()->findOrFail($courseId)->delete();
    }

    public function createUnit(array $attributes): int
    {
        return Unit::query()->create($attributes)->id;
    }

    public function updateUnit(int $unitId, array $attributes): void
    {
        Unit::query()->findOrFail($unitId)->update($attributes);
    }

    public function deleteUnit(int $unitId): void
    {
        $unit = Unit::query()->findOrFail($unitId);
        $lessonIds = $unit->lessons()->pluck('id');

        if (Schema::hasTable('lesson_progress')
            && DB::table('lesson_progress')->whereIn('lesson_id', $lessonIds)->exists()) {
            throw new DomainException('Las unidades con lecciones que tienen progreso no se pueden eliminar.');
        }

        $unit->delete();
    }

    public function reorderUnits(array $unitIds): void
    {
        $units = Unit::query()->whereIn('id', $unitIds)->get();

        if ($unitIds === []
            || $units->count() !== count(array_unique($unitIds))
            || $units->pluck('course_id')->unique()->count() !== 1
            || Unit::query()->where('course_id', $units->first()->course_id)->count() !== count($unitIds)) {
            throw new DomainException('Las unidades deben pertenecer al mismo curso.');
        }

        DB::transaction(function () use ($unitIds): void {
            foreach ($unitIds as $index => $unitId) {
                Unit::query()->whereKey($unitId)->update(['position' => $index + 1]);
            }
        });
    }

    public function reorderLessons(array $lessonIds): void
    {
        $lessons = Lesson::query()->whereIn('id', $lessonIds)->get();

        if ($lessonIds === []
            || $lessons->count() !== count(array_unique($lessonIds))
            || $lessons->pluck('unit_id')->unique()->count() !== 1
            || Lesson::query()->where('unit_id', $lessons->first()->unit_id)->count() !== count($lessonIds)) {
            throw new DomainException('Las lecciones deben pertenecer a la misma unidad.');
        }

        DB::transaction(function () use ($lessonIds): void {
            foreach ($lessonIds as $index => $lessonId) {
                Lesson::query()->whereKey($lessonId)->update([
                    'position' => $index + 1,
                    'lock_version' => DB::raw('lock_version + 1'),
                ]);
            }
        });
    }

    public function createLesson(array $attributes): int
    {
        return Lesson::query()->create($attributes)->id;
    }

    public function updateLesson(int $lessonId, int $expectedVersion, array $attributes): void
    {
        $updated = Lesson::query()
            ->whereKey($lessonId)
            ->where('lock_version', $expectedVersion)
            ->update([...$attributes, 'lock_version' => DB::raw('lock_version + 1')]);

        if ($updated !== 1) {
            throw new StaleContentVersionException(
                'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.',
            );
        }
    }

    public function archiveLesson(int $lessonId): void
    {
        Lesson::query()->findOrFail($lessonId)->update([
            'is_archived' => true,
            'lock_version' => DB::raw('lock_version + 1'),
        ]);
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $hasProgress = Schema::hasTable('lesson_progress')
            && DB::table('lesson_progress')->where('lesson_id', $lessonId)->exists();

        (new LessonEntity(
            $lesson->id,
            $lesson->unit_id,
            $lesson->title,
            $lesson->position,
            ContentType::from($lesson->content_type),
            $lesson->content,
            $lesson->is_archived,
        ))->assertCanBeDeleted($hasProgress);

        $lesson->delete();
    }
}
