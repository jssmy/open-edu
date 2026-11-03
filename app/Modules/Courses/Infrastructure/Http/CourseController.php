<?php

namespace App\Modules\Courses\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Courses\Application\AssignTeachers;
use App\Modules\Courses\Application\CreateCourse;
use App\Modules\Courses\Application\DeleteCourse;
use App\Modules\Courses\Application\SetCourseStatus;
use App\Modules\Courses\Application\UpdateCourse;
use App\Modules\Courses\Domain\CourseStatus;
use App\Modules\Courses\Infrastructure\Http\Requests\AssignCourseTeachersRequest;
use App\Modules\Courses\Infrastructure\Http\Requests\SaveCourseRequest;
use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $courses = Course::query()
            ->with('teachers:id,name')
            ->when(
                $request->user()->hasRole('docente'),
                fn ($query) => $query->whereHas('teachers', fn ($teachers) => $teachers->whereKey($request->user()->id)),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'objectives' => $course->objectives,
                'status' => $course->status,
                'teachers' => $course->teachers->map(fn ($teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ])->all(),
            ])->all();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'canManageCourses' => $request->user()->can('courses.manage'),
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $course->load([
            'teachers:id,name',
            'units.lessons',
            'assessments:id,course_id,title,is_published',
        ]);

        return Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'objectives' => $course->objectives,
                'status' => $course->status,
                'teachers' => $course->teachers->map(fn ($teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ])->all(),
                'units' => $course->units->map(fn ($unit): array => [
                    'id' => $unit->id,
                    'title' => $unit->title,
                    'position' => $unit->position,
                    'lessons' => $unit->lessons->map(fn ($lesson): array => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'position' => $lesson->position,
                        'content_type' => $lesson->content_type,
                        'content' => $lesson->content,
                        'is_archived' => $lesson->is_archived,
                    ])->all(),
                ])->all(),
                'assessments' => $course->assessments->map(fn ($assessment): array => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'is_published' => $assessment->is_published,
                ])->all(),
            ],
            'canManageCourses' => $request->user()->can('courses.manage'),
            'canManageContent' => $request->user()->can('content.manage'),
            'availableTeachers' => $request->user()->can('courses.manage')
                ? User::role('docente')->orderBy('name')->get(['id', 'name'])->map(fn (User $teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ])->all()
                : [],
        ]);
    }

    public function store(SaveCourseRequest $request, CreateCourse $create): RedirectResponse
    {
        $create($request->validated());

        return to_route('courses.index')->with('success', 'Curso creado.');
    }

    public function update(SaveCourseRequest $request, Course $course, UpdateCourse $update): RedirectResponse
    {
        $update($course->id, $request->validated());

        return to_route('courses.show', $course)->with('success', 'Curso actualizado.');
    }

    public function destroy(Request $request, Course $course, DeleteCourse $delete): RedirectResponse
    {
        $data = $request->validate(['confirm_impact' => ['sometimes', 'boolean']]);
        $delete($course->id, (bool) ($data['confirm_impact'] ?? false));

        return to_route('courses.index')->with('success', 'Curso eliminado.');
    }

    public function status(Request $request, Course $course, SetCourseStatus $setStatus): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(CourseStatus::class)]]);
        $setStatus($course->id, CourseStatus::from($data['status']));

        return to_route('courses.show', $course)->with('success', 'Estado del curso actualizado.');
    }

    public function teachers(
        AssignCourseTeachersRequest $request,
        Course $course,
        AssignTeachers $assignTeachers,
    ): RedirectResponse {
        $assignTeachers($course->id, $request->validated('teachers'));

        return to_route('courses.show', $course)->with('success', 'Docentes asignados.');
    }
}
