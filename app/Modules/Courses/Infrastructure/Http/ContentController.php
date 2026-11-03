<?php

namespace App\Modules\Courses\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Application\ManageCourseContent;
use App\Modules\Courses\Domain\FileStorage;
use App\Modules\Courses\Infrastructure\Http\Requests\ReorderLessonsRequest;
use App\Modules\Courses\Infrastructure\Http\Requests\ReorderUnitsRequest;
use App\Modules\Courses\Infrastructure\Http\Requests\SaveLessonRequest;
use App\Modules\Courses\Infrastructure\Http\Requests\SaveUnitRequest;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Shared\Exceptions\StaleContentVersionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function createLesson(Unit $unit): Response
    {
        Gate::authorize('manageContent', $unit->course);

        return Inertia::render('Courses/LessonEdit', [
            'unit' => [
                'id' => $unit->id,
                'title' => $unit->title,
                'course_id' => $unit->course_id,
                'course_name' => $unit->course->name,
            ],
            'lesson' => null,
            'nextPosition' => (int) $unit->lessons()->max('position') + 1,
        ]);
    }

    public function editLesson(Lesson $lesson): Response
    {
        Gate::authorize('manageContent', $lesson->unit->course);

        return Inertia::render('Courses/LessonEdit', [
            'unit' => [
                'id' => $lesson->unit->id,
                'title' => $lesson->unit->title,
                'course_id' => $lesson->unit->course_id,
                'course_name' => $lesson->unit->course->name,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'position' => $lesson->position,
                'content_type' => $lesson->content_type,
                'content' => $lesson->content,
                'lock_version' => $lesson->lock_version,
            ],
            'nextPosition' => $lesson->position,
        ]);
    }

    public function storeUnit(SaveUnitRequest $request, int $courseId, ManageCourseContent $content): RedirectResponse
    {
        $course = Course::query()->findOrFail($courseId);
        Gate::authorize('manageContent', $course);
        $data = $request->validated();
        $content->createUnit(['course_id' => $course->id, ...$data]);

        return to_route('courses.show', $course)->with('success', 'Unidad creada.');
    }

    public function updateUnit(SaveUnitRequest $request, Unit $unit, ManageCourseContent $content): RedirectResponse
    {
        Gate::authorize('manageContent', $unit->course);
        $content->updateUnit($unit->id, $request->validated());

        return to_route('courses.show', $unit->course)->with('success', 'Unidad actualizada.');
    }

    public function deleteUnit(
        Unit $unit,
        ManageCourseContent $content,
        FileStorage $files,
    ): RedirectResponse {
        Gate::authorize('manageContent', $unit->course);
        $course = $unit->course;
        $storedPaths = $unit->lessons()
            ->whereNotNull('content')
            ->get(['content'])
            ->pluck('content')
            ->filter(fn ($path): bool => is_string($path) && str_starts_with($path, 'courses/'));
        $content->deleteUnit($unit->id);

        foreach ($storedPaths as $path) {
            $files->delete($path);
        }

        return to_route('courses.show', $course)->with('success', 'Unidad eliminada.');
    }

    public function reorderUnits(
        ReorderUnitsRequest $request,
        ManageCourseContent $content,
    ): RedirectResponse {
        $unitIds = $request->validated('unit_ids');
        $course = Unit::query()->findOrFail($unitIds[0])->course;
        Gate::authorize('manageContent', $course);
        $content->reorderUnits($unitIds);

        return to_route('courses.show', $course)->with('success', 'Orden de unidades actualizado.');
    }

    public function reorderLessons(
        ReorderLessonsRequest $request,
        ManageCourseContent $content,
    ): RedirectResponse {
        $lessonIds = $request->validated('lesson_ids');
        $unit = Lesson::query()->findOrFail($lessonIds[0])->unit;
        Gate::authorize('manageContent', $unit->course);
        $content->reorderLessons($lessonIds);

        return to_route('courses.show', $unit->course)->with('success', 'Orden de lecciones actualizado.');
    }

    public function storeLesson(
        SaveLessonRequest $request,
        Unit $unit,
        ManageCourseContent $content,
        FileStorage $files,
    ): RedirectResponse {
        Gate::authorize('manageContent', $unit->course);
        $data = $request->validated();
        $data['unit_id'] = $unit->id;
        $data['content'] = $request->hasFile('file')
            ? $files->store(
                'courses/'.$unit->course_id.'/lessons',
                $request->file('file')->getPathname(),
                $request->file('file')->hashName(),
            )
            : ($data['content'] ?? null);
        unset($data['file']);
        $content->createLesson($data);

        return to_route('courses.show', $unit->course)->with('success', 'Lección creada.');
    }

    public function updateLesson(
        SaveLessonRequest $request,
        Lesson $lesson,
        ManageCourseContent $content,
        FileStorage $files,
    ): RedirectResponse {
        Gate::authorize('manageContent', $lesson->unit->course);
        $data = $request->validated();
        $expectedVersion = (int) $data['lock_version'];
        unset($data['lock_version']);
        $oldContent = $lesson->content;
        $data['content'] = $request->hasFile('file')
            ? $files->store(
                'courses/'.$lesson->unit->course_id.'/lessons',
                $request->file('file')->getPathname(),
                $request->file('file')->hashName(),
            )
            : ($data['content'] ?? null);
        unset($data['file']);
        try {
            $content->updateLesson($lesson->id, $expectedVersion, $data);
        } catch (StaleContentVersionException $exception) {
            if ($request->hasFile('file') && is_string($data['content']) && str_starts_with($data['content'], 'courses/')) {
                $files->delete($data['content']);
            }

            throw $exception;
        }

        if ($request->hasFile('file') && is_string($oldContent) && str_starts_with($oldContent, 'courses/')) {
            $files->delete($oldContent);
        }

        return to_route('courses.show', $lesson->unit->course)->with('success', 'Lección actualizada.');
    }

    public function archiveLesson(Lesson $lesson, ManageCourseContent $content): RedirectResponse
    {
        Gate::authorize('manageContent', $lesson->unit->course);
        $content->archiveLesson($lesson->id);

        return to_route('courses.show', $lesson->unit->course)->with('success', 'Lección archivada.');
    }

    public function deleteLesson(
        Lesson $lesson,
        ManageCourseContent $content,
        FileStorage $files,
    ): RedirectResponse {
        Gate::authorize('manageContent', $lesson->unit->course);
        $course = $lesson->unit->course;
        $path = $lesson->content;
        $content->deleteLesson($lesson->id);

        if (is_string($path) && str_starts_with($path, 'courses/')) {
            $files->delete($path);
        }

        return to_route('courses.show', $course)->with('success', 'Lección eliminada.');
    }
}
