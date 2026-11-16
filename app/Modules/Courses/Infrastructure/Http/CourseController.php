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
use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use App\Modules\Enrollments\Application\EnrollTeacher;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $user = $request->user();
        $canManageCourses = $user->can('courses.manage');
        $isRoleCatalogView = ! $canManageCourses;
        $userRoles = $user->getRoleNames()->values()->all();

        $term = trim($request->string('q')->toString());
        $status = CourseStatus::tryFrom($request->string('status')->toString());
        $categoryId = $request->integer('category_id') ?: null;

        $enrolledCourseIds = array_fill_keys(
            Enrollment::query()
                ->where('user_id', $user->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->pluck('course_id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
            true,
        );

        $paginator = Course::query()
            ->with([
                'teachers:id,name',
                'category:id,name,slug,role_name',
                'units.lessons' => fn ($query) => $query->where('is_archived', false)->select(['id', 'unit_id']),
            ])
            ->withCount('units')
            ->when(
                $isRoleCatalogView,
                fn ($query) => $query
                    ->where('status', CourseStatus::Published->value)
                    ->forRoles($userRoles),
            )
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $like)
                    ->orWhereHas('teachers', fn ($teachers) => $teachers->where('name', 'like', $like)));
            })
            ->when($canManageCourses && $status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));

        $completedLessonIds = [];
        if ($enrolledCourseIds !== []) {
            $pageLessonIds = $paginator->getCollection()
                ->flatMap(fn (Course $course) => $course->units->flatMap(fn ($unit) => $unit->lessons->pluck('id')))
                ->values()
                ->all();

            if ($pageLessonIds !== []) {
                $completedLessonIds = array_fill_keys(
                    DB::table('lesson_progress')
                        ->where('user_id', $user->id)
                        ->whereIn('lesson_id', $pageLessonIds)
                        ->pluck('lesson_id')
                        ->map(fn ($id): int => (int) $id)
                        ->all(),
                    true,
                );
            }
        }

        $courses = [
            'data' => $paginator->getCollection()->map(function (Course $course) use ($isRoleCatalogView, $enrolledCourseIds, $completedLessonIds): array {
                $isEnrolled = isset($enrolledCourseIds[$course->id]);
                $roleName = $course->category?->role_name ?? ($course->target_audience === 'teacher' ? 'docente' : 'estudiante');

                $progress = null;
                if ($isEnrolled) {
                    $lessonIds = $course->units
                        ->flatMap(fn ($unit) => $unit->lessons->pluck('id'))
                        ->map(fn ($id): int => (int) $id)
                        ->all();
                    $totalLessons = count($lessonIds);
                    $completedLessons = count(array_filter($lessonIds, fn (int $id): bool => isset($completedLessonIds[$id])));
                    $percentage = $totalLessons === 0 ? 0 : (int) round(($completedLessons / $totalLessons) * 100);

                    $progress = [
                        'completed_lessons' => $completedLessons,
                        'total_lessons' => $totalLessons,
                        'percentage' => $percentage,
                    ];
                }

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'excerpt' => Str::limit(trim(RichTextDocumentValidator::plainText($course->description)), 180),
                    'cover_url' => $course->coverUrl(),
                    'units_count' => $course->units_count,
                    'status' => $course->status,
                    'category_id' => $course->category_id,
                    'category_name' => $course->category?->name,
                    'category_role_name' => $roleName,
                    'issues_certificate' => (bool) ($course->issues_certificate ?? true),
                    'is_enrolled' => $isEnrolled,
                    'can_self_enroll' => $isRoleCatalogView && ! $isEnrolled,
                    'study_url' => $isEnrolled ? "/my/courses/{$course->id}" : null,
                    'progress' => $progress,
                    'teachers' => $course->teachers->map(fn ($teacher): array => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                    ])->all(),
                ];
            })->all(),
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
            'categories' => $this->activeCategories($isRoleCatalogView ? $userRoles : null),
            'roles' => $canManageCourses ? $this->availableRoles() : [],
            'filters' => [
                'q' => $term,
                'status' => $status?->value ?? '',
                'category_id' => $categoryId ? (string) $categoryId : '',
            ],
            'canManageCourses' => $canManageCourses,
            'isRoleCatalogView' => $isRoleCatalogView,
        ]);
    }

    public function selfEnroll(
        Request $request,
        Course $course,
        EnrollTeacher $enrollTeacher,
    ): RedirectResponse {
        $enrollTeacher($request->user()->id, $course->id, $request->user()->id);

        return to_route('study.courses.show', $course)->with('success', 'Te has inscrito exitosamente en el curso.');
    }

    public function create(): Response
    {
        Gate::authorize('courses.manage');

        return Inertia::render('Courses/Edit', [
            'course' => null,
            'categories' => $this->activeCategories(),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('Courses/Edit', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'category_id' => $course->category_id,
                'issues_certificate' => (bool) ($course->issues_certificate ?? true),
                'description' => $course->description,
                'objectives' => $course->objectives,
                'intro_video_url' => $course->introVideoUrl(),
                'cover_url' => $course->coverUrl(),
                'has_custom_cover' => $course->cover_path !== null,
                'lock_version' => $course->lock_version,
            ],
            'categories' => $this->activeCategories(),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $course->load([
            'category:id,name,slug,role_name',
            'teachers:id,name',
            'units.lessons',
            'assessments:id,course_id,title,is_published',
        ]);

        return Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'category_id' => $course->category_id,
                'category_name' => $course->category?->name,
                'category_role_name' => $course->category?->role_name ?? ($course->target_audience === 'teacher' ? 'docente' : 'estudiante'),
                'issues_certificate' => (bool) ($course->issues_certificate ?? true),
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
            'canManageContent' => $request->user()->can('content.manage') && (! $request->user()->hasRole('docente') || $course->teachers()->whereKey($request->user()->id)->exists()),
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
     * @param  array{name: string, category_id?: int|null, target_audience?: string, issues_certificate?: bool, description?: array|null, objectives?: array|null, intro_video_url?: string|null}  $attributes
     * @return array{name: string, category_id?: int|null, target_audience?: string, issues_certificate?: bool, description: array|null, objectives: array|null, intro_video_provider: string|null, intro_video_id: string|null}
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

    /**
     * @param  list<string>|null  $filterRoles
     * @return list<array{id: int, name: string, slug: string, role_name: string}>
     */
    private function activeCategories(?array $filterRoles = null): array
    {
        return CourseCategory::query()
            ->where('is_active', true)
            ->when($filterRoles !== null, fn ($query) => $query->whereIn('role_name', $filterRoles))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'role_name'])
            ->map(fn (CourseCategory $cat): array => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'role_name' => $cat->role_name,
            ])
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function availableRoles(): array
    {
        $labels = [
            'estudiante' => 'Estudiante',
            'docente' => 'Docente',
            'administrador' => 'Administrador',
        ];

        $roles = Role::query()
            ->orderByRaw("CASE WHEN name = 'estudiante' THEN 0 WHEN name = 'docente' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($roles === []) {
            $roles = ['estudiante', 'docente'];
        }

        return array_map(fn (string $name): array => [
            'value' => $name,
            'label' => $labels[$name] ?? ucfirst($name),
        ], $roles);
    }
}
