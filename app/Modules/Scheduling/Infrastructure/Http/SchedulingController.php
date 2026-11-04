<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Application\DeleteSchedule;
use App\Modules\Scheduling\Application\SaveSchedule;
use App\Modules\Scheduling\Infrastructure\Http\Requests\SaveScheduleRequest;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchedulingController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('scheduling.manage');

        $courseId = $request->integer('course') ?: null;
        $teacherId = $request->integer('teacher') ?: null;
        $classroomId = $request->integer('classroom') ?: null;
        $periodId = $request->integer('period') ?: null;
        $term = trim($request->string('q')->toString());

        $periods = AcademicPeriod::query()->orderByDesc('starts_on')->get();
        if ($periodId === null && $periods->isNotEmpty()) {
            $periodId = $periods->first()->id;
        }

        $baseQuery = CourseSchedule::query()
            ->whereNotNull('academic_period_id')
            ->with(['course:id,name', 'teacher:id,name', 'period:id,name,starts_on,ends_on', 'classroom:id,name', 'blocks'])
            ->when($periodId !== null, fn ($q) => $q->where('academic_period_id', $periodId))
            ->when($courseId !== null, fn ($q) => $q->where('course_id', $courseId))
            ->when($teacherId !== null, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($classroomId !== null, fn ($q) => $q->where('classroom_id', $classroomId))
            ->when($term !== '', function ($q) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($inner) => $inner
                    ->whereHas('course', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', $like)));
            })
            ->orderByDesc('id');

        $paginator = (clone $baseQuery)->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));
        $calendarSchedules = (clone $baseQuery)->limit(500)->get();

        $pendingCount = CourseSchedule::query()->whereNull('academic_period_id')->count();

        return Inertia::render('Scheduling/Index', [
            'schedules' => [
                'data' => $paginator->getCollection()->map(fn (CourseSchedule $s) => $this->serializeSchedule($s))->all(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => (int) $paginator->firstItem(),
                    'to' => (int) $paginator->lastItem(),
                ],
            ],
            'calendarBlocks' => $this->flattenCalendarBlocks($calendarSchedules),
            'filters' => [
                'q' => $term,
                'course' => $courseId ? (string) $courseId : '',
                'teacher' => $teacherId ? (string) $teacherId : '',
                'classroom' => $classroomId ? (string) $classroomId : '',
                'period' => $periodId ? (string) $periodId : '',
            ],
            'courses' => Course::query()->orderBy('name')->get(['id', 'name'])->all(),
            'teachers' => User::role('docente')->where('is_active', true)->orderBy('name')->get(['id', 'name'])->all(),
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'capacity'])->all(),
            'periods' => $periods->map(fn (AcademicPeriod $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'starts_on' => $p->starts_on->toDateString(),
                'ends_on' => $p->ends_on->toDateString(),
                'has_ended' => $p->hasEnded(),
            ])->all(),
            'pendingCount' => $pendingCount,
        ]);
    }

    public function pending(Request $request): Response
    {
        Gate::authorize('scheduling.manage');

        $term = trim($request->string('q')->toString());

        $paginator = CourseSchedule::query()
            ->whereNull('academic_period_id')
            ->with(['course:id,name', 'teacher:id,name'])
            ->when($term !== '', function ($q) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($inner) => $inner
                    ->whereHas('course', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', $like)));
            })
            ->orderBy('id')
            ->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));

        return Inertia::render('Scheduling/Pending', [
            'pending' => [
                'data' => $paginator->getCollection()->map(fn (CourseSchedule $s): array => [
                    'id' => $s->id,
                    'course_id' => $s->course_id,
                    'course_name' => $s->course?->name ?? '',
                    'teacher_id' => $s->teacher_id,
                    'teacher_name' => $s->teacher?->name ?? '',
                ])->all(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => (int) $paginator->firstItem(),
                    'to' => (int) $paginator->lastItem(),
                ],
            ],
            'filters' => ['q' => $term],
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->all(),
            'periods' => AcademicPeriod::query()->orderByDesc('starts_on')->get()->filter(fn (AcademicPeriod $p) => ! $p->hasEnded())->values()->map(fn (AcademicPeriod $p): array => [
                'id' => $p->id,
                'name' => $p->name,
            ])->all(),
        ]);
    }

    public function mySchedule(Request $request): Response
    {
        Gate::authorize('scheduling.view-own');

        $schedules = CourseSchedule::query()
            ->where('teacher_id', $request->user()->id)
            ->whereNotNull('academic_period_id')
            ->with(['course:id,name', 'teacher:id,name', 'period:id,name,starts_on,ends_on', 'classroom:id,name', 'blocks'])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Scheduling/MySchedule', [
            'schedules' => $schedules->map(fn (CourseSchedule $s) => $this->serializeSchedule($s))->all(),
            'calendarBlocks' => $this->flattenCalendarBlocks($schedules, true),
        ]);
    }

    public function store(SaveScheduleRequest $request, SaveSchedule $save): RedirectResponse
    {
        $validated = $request->validated();
        $blocks = $validated['blocks'];
        unset($validated['blocks']);

        $save($validated, $blocks);

        return back()->with('success', 'Programación guardada.');
    }

    public function update(
        SaveScheduleRequest $request,
        CourseSchedule $schedule,
        SaveSchedule $save,
    ): RedirectResponse {
        $validated = $request->validated();
        $blocks = $validated['blocks'];
        unset($validated['blocks']);

        $save($validated, $blocks, $schedule->id);

        return back()->with('success', 'Programación actualizada.');
    }

    public function destroy(CourseSchedule $schedule, DeleteSchedule $delete): RedirectResponse
    {
        Gate::authorize('scheduling.manage');

        $delete($schedule->id);

        return back()->with('success', 'Programación eliminada.');
    }

    /** @return array<string, mixed> */
    private function serializeSchedule(CourseSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'course_id' => $schedule->course_id,
            'course_name' => $schedule->course?->name ?? '',
            'teacher_id' => $schedule->teacher_id,
            'teacher_name' => $schedule->teacher?->name ?? '',
            'academic_period_id' => $schedule->academic_period_id,
            'period_name' => $schedule->period?->name ?? 'Por programar',
            'period_has_ended' => $schedule->period?->hasEnded() ?? false,
            'modality' => $schedule->modality,
            'classroom_id' => $schedule->classroom_id,
            'classroom_name' => $schedule->classroom?->name,
            'blocks' => $schedule->blocks->map(fn ($b): array => [
                'id' => $b->id,
                'day_of_week' => $b->day_of_week,
                'starts_at' => $b->starts_at,
                'ends_at' => $b->ends_at,
            ])->values()->all(),
        ];
    }

    /**
     * @param  iterable<CourseSchedule>  $schedules
     * @return list<array<string, mixed>>
     */
    private function flattenCalendarBlocks(iterable $schedules, bool $forceReadOnly = false): array
    {
        $blocks = [];
        foreach ($schedules as $schedule) {
            $readOnly = $forceReadOnly || ($schedule->period?->hasEnded() ?? false);
            foreach ($schedule->blocks->values() as $index => $block) {
                $blocks[] = [
                    'id' => $block->id,
                    'scheduleId' => $schedule->id,
                    'blockIndex' => $index,
                    'courseName' => $schedule->course?->name ?? '',
                    'teacherName' => $schedule->teacher?->name ?? '',
                    'modality' => $schedule->modality,
                    'classroomName' => $schedule->classroom?->name,
                    'dayOfWeek' => $block->day_of_week,
                    'startsAt' => $block->starts_at,
                    'endsAt' => $block->ends_at,
                    'readOnly' => $readOnly,
                ];
            }
        }

        return $blocks;
    }
}

