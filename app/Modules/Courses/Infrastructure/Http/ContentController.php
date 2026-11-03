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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ContentController extends Controller
{
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
            ->pluck('content')
            ->filter(fn ($path): bool => str_starts_with($path, 'courses/'));
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
        $oldContent = $lesson->content;
        $data['content'] = $request->hasFile('file')
            ? $files->store(
                'courses/'.$lesson->unit->course_id.'/lessons',
                $request->file('file')->getPathname(),
                $request->file('file')->hashName(),
            )
            : ($data['content'] ?? null);
        unset($data['file']);
        $content->updateLesson($lesson->id, $data);

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
