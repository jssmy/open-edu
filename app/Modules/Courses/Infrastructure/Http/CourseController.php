<?php

namespace App\Modules\Courses\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Application\CreateCourse;
use App\Modules\Courses\Application\DeleteCourse;
use App\Modules\Courses\Application\SetCourseCover;
use App\Modules\Courses\Application\SetCourseStatus;
use App\Modules\Courses\Application\UpdateCourse;
use App\Modules\Courses\Domain\CourseStatus;
use App\Modules\Courses\Domain\IntroductoryVideo;
use App\Modules\Courses\Infrastructure\Http\Requests\SaveCourseRequest;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $term = trim($request->string('q')->toString());
        $status = CourseStatus::tryFrom($request->string('status')->toString());

        $paginator = Course::query()
            ->with('teachers:id,name')
            ->withCount('units')
            ->when(
                $request->user()->hasRole('docente'),
                fn ($query) => $query->whereHas('teachers', fn ($teachers) => $teachers->whereKey($request->user()->id)),
            )
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $like)
                    ->orWhereHas('teachers', fn ($teachers) => $teachers->where('name', 'like', $like)));
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));

        $courses = [
            'data' => $paginator->getCollection()->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'excerpt' => Str::limit(trim(RichTextDocumentValidator::plainText($course->description)), 180),
                'cover_url' => $course->coverUrl(),
                'units_count' => $course->units_count,
                'status' => $course->status,
                'teachers' => $course->teachers->map(fn ($teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ])->all(),
            ])->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => (int) $paginator->firstItem(),
                'to' => (int) $paginator->lastItem(),
            ],
        ];

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'filters' => ['q' => $term, 'status' => $status?->value ?? ''],
            'canManageCourses' => $request->user()->can('courses.manage'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('courses.manage');

        return Inertia::render('Courses/Edit', [
            'course' => null,
        ]);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('courses.manage');

        return Inertia::render('Courses/Edit', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'objectives' => $course->objectives,
                'intro_video_url' => $course->introVideoUrl(),
                'cover_url' => $course->coverUrl(),
                'has_custom_cover' => $course->cover_path !== null,
                'lock_version' => $course->lock_version,
            ],
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
                'intro_video_url' => $course->introVideoUrl(),
                'intro_video_embed_url' => $course->introVideoEmbedUrl(),
                'status' => $course->status,
                'lock_version' => $course->lock_version,
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
                        'lock_version' => $lesson->lock_version,
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
        ]);
    }

    public function store(SaveCourseRequest $request, CreateCourse $create, SetCourseCover $setCover): RedirectResponse
    {
        $courseId = $create($this->normalizeCourseAttributes($request->safe()->except(['cover_image', 'remove_cover', 'rich_text_encoded'])));
        $cover = $request->file('cover_image');
        $setCover($courseId, $cover?->getPathname(), $cover?->getMimeType());

        return to_route('courses.show', $courseId)->with('success', 'Curso creado.');
    }

    public function update(
        SaveCourseRequest $request,
        Course $course,
        UpdateCourse $update,
        SetCourseCover $setCover,
    ): RedirectResponse|JsonResponse {
        $attributes = $request->safe()->except(['cover_image', 'remove_cover', 'rich_text_encoded']);
        $expectedVersion = (int) $attributes['lock_version'];
        unset($attributes['lock_version']);

        $update($course->id, $expectedVersion, $this->normalizeCourseAttributes($attributes));
        $cover = $request->file('cover_image');
        $setCover($course->id, $cover?->getPathname(), $cover?->getMimeType(), $request->boolean('remove_cover'));

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

    /**
     * @param  array{name: string, description?: array|null, objectives?: array|null, intro_video_url?: string|null}  $attributes
     * @return array{name: string, description: array|null, objectives: array|null, intro_video_provider: string|null, intro_video_id: string|null}
     */
    private function normalizeCourseAttributes(array $attributes): array
    {
        $video = IntroductoryVideo::fromUrl($attributes['intro_video_url'] ?? null);
        unset($attributes['intro_video_url']);

        return [
            ...$attributes,
            'intro_video_provider' => $video?->provider,
            'intro_video_id' => $video?->id,
        ];
    }
}
