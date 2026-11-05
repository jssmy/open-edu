<?php

namespace App\Modules\Scheduling\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Application\DeleteCourseSession;
use App\Modules\Scheduling\Application\DeleteSchedule;
use App\Modules\Scheduling\Application\SaveCourseSession;
use App\Modules\Scheduling\Application\SaveSchedule;
use App\Modules\Scheduling\Infrastructure\Http\Requests\SaveScheduleRequest;
use App\Modules\Scheduling\Infrastructure\Http\Requests\SaveSessionRequest;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;
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
        $statusFilter = trim($request->string('status')->toString());
        $term = trim($request->string('q')->toString());

        $baseQuery = CourseOffering::query()
            ->whereNotNull('starts_on')
            ->with([
                'course:id,name',
                'teachers:id,name',
                'sessions.teacher:id,name',
                'sessions.classroom:id,name,capacity',
            ])
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', 'active')])
            ->when($courseId !== null, fn ($q) => $q->where('course_id', $courseId))
            ->when($teacherId !== null, function ($q) use ($teacherId): void {
                $q->where(fn ($inner) => $inner
                    ->whereHas('teachers', fn ($t) => $t->where('users.id', $teacherId))
                    ->orWhereHas('sessions', fn ($s) => $s->where('teacher_id', $teacherId)));
            })
            ->when($classroomId !== null, fn ($q) => $q->whereHas('sessions', fn ($s) => $s->where('classroom_id', $classroomId)))
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->when($term !== '', function ($q) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($inner) => $inner
                    ->where('group_code', 'like', $like)
                    ->orWhereHas('course', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('teachers', fn ($t) => $t->where('name', 'like', $like)));
            })
            ->orderByDesc('id');

        $paginator = (clone $baseQuery)->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));
        $calendarOfferings = (clone $baseQuery)->limit(500)->get();

        $pendingCount = CourseOffering::query()->whereNull('starts_on')->count();

        return Inertia::render('Scheduling/Index', [
            'schedules' => [
                'data' => $paginator->getCollection()->map(fn (CourseOffering $o) => $this->serializeOffering($o))->all(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => (int) $paginator->firstItem(),
                    'to' => (int) $paginator->lastItem(),
                ],
            ],
            'calendarBlocks' => $this->flattenCalendarSessions($calendarOfferings),
            'calendarOfferings' => $calendarOfferings->map(fn (CourseOffering $o) => $this->serializeOffering($o))->all(),
            'filters' => [
                'q' => $term,
                'course' => $courseId ? (string) $courseId : '',
                'teacher' => $teacherId ? (string) $teacherId : '',
                'classroom' => $classroomId ? (string) $classroomId : '',
                'status' => $statusFilter,
            ],
            'courses' => Course::query()
                ->with('offerings:id,course_id,group_code')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Course $course): array => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'next_group_code' => CourseOffering::nextGroupCodeForCourse(
                        $course->id,
                        null,
                        $course->offerings->pluck('group_code')->all(),
                    ),
                ])
                ->all(),
            'teachers' => User::role('docente')->where('is_active', true)->orderBy('name')->get(['id', 'name'])->all(),
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'capacity'])->all(),
            'pendingCount' => $pendingCount,
        ]);
    }

    public function pending(Request $request): Response
    {
        Gate::authorize('scheduling.manage');

        $term = trim($request->string('q')->toString());

        $paginator = CourseOffering::query()
            ->whereNull('starts_on')
            ->with(['course:id,name', 'teachers:id,name'])
            ->when($term !== '', function ($q) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($inner) => $inner
                    ->whereHas('course', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('teachers', fn ($t) => $t->where('name', 'like', $like)));
            })
            ->orderBy('id')
            ->paginate(10, ['*'], 'page', max(1, $request->integer('page', 1)));

        return Inertia::render('Scheduling/Pending', [
            'pending' => [
                'data' => $paginator->getCollection()->map(function (CourseOffering $o): array {
                    $firstTeacher = $o->teachers->first();

                    return [
                        'id' => $o->id,
                        'course_id' => $o->course_id,
                        'course_name' => $o->course?->name ?? '',
                        'group_code' => $o->group_code,
                        'teacher_id' => $firstTeacher?->id,
                        'teacher_ids' => $o->teachers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                        'teacher_name' => $o->teachers->pluck('name')->join(', '),
                    ];
                })->all(),
                'meta' => [
                    'page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => (int) $paginator->firstItem(),
                    'to' => (int) $paginator->lastItem(),
                ],
            ],
            'filters' => ['q' => $term],
            'teachers' => User::role('docente')->where('is_active', true)->orderBy('name')->get(['id', 'name'])->all(),
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'capacity'])->all(),
        ]);
    }

    public function mySchedule(Request $request): Response
    {
        Gate::authorize('scheduling.view-own');

        $teacherId = $request->user()->id;

        $offerings = CourseOffering::query()
            ->whereNotNull('starts_on')
            ->where(function ($q) use ($teacherId): void {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $teacherId))
                    ->orWhereHas('sessions', fn ($s) => $s->where('teacher_id', $teacherId));
            })
            ->with([
                'course:id,name',
                'teachers:id,name',
                'sessions' => fn ($s) => $s->where('teacher_id', $teacherId)->orderBy('session_date')->orderBy('starts_at'),
                'sessions.teacher:id,name',
                'sessions.classroom:id,name,capacity',
            ])
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Scheduling/MySchedule', [
            'schedules' => $offerings->map(fn (CourseOffering $o) => $this->serializeOffering($o))->all(),
            'calendarBlocks' => $this->flattenCalendarSessions($offerings, true),
        ]);
    }

    public function store(SaveScheduleRequest $request, SaveSchedule $save): RedirectResponse
    {
        $validated = $request->validated();
        $teacherIds = $validated['teacher_ids'];
        $sessions = $validated['sessions'];
        unset($validated['teacher_ids'], $validated['sessions']);

        $save($validated, $teacherIds, $sessions);

        return back()->with('success', 'Programación académica guardada.');
    }

    public function update(
        SaveScheduleRequest $request,
        CourseOffering $schedule,
        SaveSchedule $save,
    ): RedirectResponse {
        $validated = $request->validated();
        $teacherIds = $validated['teacher_ids'];
        $sessions = $validated['sessions'];
        unset($validated['teacher_ids'], $validated['sessions']);

        $save($validated, $teacherIds, $sessions, $schedule->id);

        return back()->with('success', 'Programación académica actualizada.');
    }

    public function destroy(CourseOffering $schedule, DeleteSchedule $delete): RedirectResponse
    {
        Gate::authorize('scheduling.manage');

        $delete($schedule->id);

        return back()->with('success', 'Programación académica eliminada.');
    }

    public function storeSession(
        SaveSessionRequest $request,
        CourseOffering $offering,
        SaveCourseSession $saveSession,
    ): RedirectResponse {
        $saveSession($offering->id, $request->validated());

        return back()->with('success', 'Sesión agregada.');
    }

    public function updateSession(
        SaveSessionRequest $request,
        CourseSession $session,
        SaveCourseSession $saveSession,
    ): RedirectResponse {
        $saveSession($session->course_offering_id, $request->validated(), $session->id);

        return back()->with('success', 'Sesión actualizada.');
    }

    public function destroySession(
        CourseSession $session,
        DeleteCourseSession $deleteSession,
    ): RedirectResponse {
        Gate::authorize('scheduling.manage');

        $deleteSession($session->id);

        return back()->with('success', 'Sesión eliminada.');
    }

    /** @return array<string, mixed> */
    private function serializeOffering(CourseOffering $offering): array
    {
        $enrolledCount = (int) ($offering->enrolled_count ?? 0);
        $maxStudents = (int) $offering->max_students;

        return [
            'id' => $offering->id,
            'course_id' => $offering->course_id,
            'course_name' => $offering->course?->name ?? '',
            'group_code' => $offering->group_code,
            'starts_on' => $offering->starts_on?->toDateString(),
            'ends_on' => $offering->ends_on?->toDateString(),
            'max_students' => $maxStudents,
            'enrolled_count' => $enrolledCount,
            'available_spots' => max(0, $maxStudents - $enrolledCount),
            'modality' => $offering->modality,
            'status' => $offering->effectiveStatus(),
            'raw_status' => $offering->status,
            'teacher_ids' => $offering->teachers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'teacher_names' => $offering->teachers->pluck('name')->values()->all(),
            'teacher_name' => $offering->teachers->pluck('name')->join(', '),
            'sessions' => $offering->sessions->map(fn (CourseSession $s): array => [
                'id' => $s->id,
                'title' => $s->title,
                'session_date' => $s->session_date->toDateString(),
                'day_of_week' => (int) $s->session_date->dayOfWeekIso,
                'starts_at' => $s->starts_at,
                'ends_at' => $s->ends_at,
                'teacher_id' => $s->teacher_id,
                'teacher_name' => $s->teacher?->name ?? '',
                'classroom_id' => $s->classroom_id,
                'classroom_name' => $s->classroom?->name,
                'meeting_url' => $s->meeting_url,
                'status' => $s->effectiveStatus(),
                'raw_status' => $s->status,
            ])->values()->all(),
        ];
    }

    /**
     * @param  iterable<CourseOffering>  $offerings
     * @return list<array<string, mixed>>
     */
    private function flattenCalendarSessions(iterable $offerings, bool $forceReadOnly = false): array
    {
        $events = [];
        foreach ($offerings as $offering) {
            foreach ($offering->sessions->values() as $index => $session) {
                $effectiveSessionStatus = $session->effectiveStatus();
                if ($effectiveSessionStatus === 'cancelled') {
                    continue;
                }

                $events[] = [
                    'id' => $session->id,
                    'scheduleId' => $offering->id,
                    'sessionId' => $session->id,
                    'blockIndex' => $index,
                    'title' => $session->title,
                    'groupCode' => $offering->group_code,
                    'courseId' => $offering->course_id,
                    'courseName' => $offering->course?->name ?? '',
                    'teacherId' => $session->teacher_id,
                    'teacherName' => $session->teacher?->name ?? '',
                    'modality' => $offering->modality,
                    'classroomName' => $session->classroom?->name,
                    'meetingUrl' => $session->meeting_url,
                    'sessionDate' => $session->session_date->toDateString(),
                    'dayOfWeek' => (int) $session->session_date->dayOfWeekIso,
                    'startsAt' => $session->starts_at,
                    'endsAt' => $session->ends_at,
                    'status' => $effectiveSessionStatus,
                    'readOnly' => $forceReadOnly,
                ];
            }
        }

        return $events;
    }
}
