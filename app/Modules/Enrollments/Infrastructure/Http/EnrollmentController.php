<?php

namespace App\Modules\Enrollments\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Application\ChangeEnrollmentStatus;
use App\Modules\Enrollments\Application\EnrollStudent;
use App\Modules\Enrollments\Application\UnenrollStudent;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Http\Requests\ChangeEnrollmentStatusRequest;
use App\Modules\Enrollments\Infrastructure\Http\Requests\StoreEnrollmentRequest;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;
use App\Modules\Students\Domain\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function __construct(private readonly StudentRepository $students) {}

    public function index(Request $request): Response
    {
        Gate::authorize('enrollments.manage');

        $activeStudents = array_values(array_map(
            fn ($student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'identifier' => $student->identifier,
            ],
            array_filter($this->students->all(), fn ($s): bool => $s->isActive),
        ));

        $requestedStudentId = $request->integer('student_id') ?: null;
        $selectedStudentId = null;
        if ($requestedStudentId !== null) {
            foreach ($activeStudents as $s) {
                if ($s['id'] === $requestedStudentId) {
                    $selectedStudentId = $requestedStudentId;
                    break;
                }
            }
        }
        if ($selectedStudentId === null && $activeStudents !== []) {
            $selectedStudentId = $activeStudents[0]['id'];
        }

        $search = trim($request->string('q')->toString());

        $activeEnrollments = $selectedStudentId !== null
            ? Enrollment::query()
                ->where('student_id', $selectedStudentId)
                ->where('status', EnrollmentStatus::Active)
                ->with([
                    'course:id,name',
                    'courseOffering.teachers:id,name',
                    'courseOffering.sessions.teacher:id,name',
                    'courseOffering.sessions.classroom:id,name',
                ])
                ->orderByDesc('enrolled_at')
                ->get()
            : collect();

        $enrolledByCourse = [];
        foreach ($activeEnrollments as $enrollment) {
            $enrolledByCourse[$enrollment->course_id] = [
                'enrollment_id' => $enrollment->id,
                'course_offering_id' => $enrollment->course_offering_id,
                'group_code' => $enrollment->courseOffering?->group_code,
            ];
        }

        $coursesQuery = Course::query()
            ->where('status', 'published')
            ->with([
                'offerings' => fn ($q) => $q
                    ->whereNotNull('starts_on')
                    ->with([
                        'teachers:id,name',
                        'sessions.teacher:id,name',
                        'sessions.classroom:id,name',
                    ])
                    ->withCount(['enrollments as enrolled_count' => fn ($e) => $e->where('status', 'active')])
                    ->orderBy('starts_on')
                    ->orderBy('id'),
            ])
            ->when($search !== '', function ($q) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where('name', 'like', $like);
            })
            ->orderBy('name')
            ->get();

        $courses = $coursesQuery->map(function (Course $course) use ($enrolledByCourse): array {
            $offerings = $course->offerings
                ->filter(fn (CourseOffering $offering): bool => $offering->isEnrollable())
                ->values()
                ->map(fn (CourseOffering $offering): array => $this->serializeOffering($offering))
                ->all();

            $totalAvailableSpots = array_sum(array_column($offerings, 'available_spots'));
            $enrollmentInfo = $enrolledByCourse[$course->id] ?? null;

            return [
                'id' => $course->id,
                'name' => $course->name,
                'code' => null,
                'description' => is_string($course->description) ? $course->description : null,
                'offerings_count' => count($offerings),
                'total_available_spots' => $totalAvailableSpots,
                'is_enrolled' => $enrollmentInfo !== null,
                'enrolled_group_code' => $enrollmentInfo['group_code'] ?? null,
                'enrollment_id' => $enrollmentInfo['enrollment_id'] ?? null,
                'offerings' => $offerings,
            ];
        })->values()->all();

        $studentEnrollments = $activeEnrollments->map(function (Enrollment $enrollment): array {
            $offering = $enrollment->courseOffering;

            return [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'course_name' => $enrollment->course?->name ?? '',
                'course_offering_id' => $enrollment->course_offering_id,
                'group_code' => $offering?->group_code ?? 'Sin grupo',
                'starts_on' => $offering?->starts_on?->toDateString(),
                'ends_on' => $offering?->ends_on?->toDateString(),
                'modality' => $offering?->modality ?? 'in_person',
                'teacher_name' => $offering ? $offering->teachers->pluck('name')->join(', ') : '',
                'enrolled_at' => $enrollment->enrolled_at->toDateString(),
            ];
        })->values()->all();

        $calendarBlocks = $this->buildStudentCalendarBlocks($activeEnrollments);

        return Inertia::render('Enrollments/Index', [
            'students' => $activeStudents,
            'selectedStudentId' => $selectedStudentId,
            'courses' => $courses,
            'studentEnrollments' => $studentEnrollments,
            'calendarBlocks' => $calendarBlocks,
            'filters' => [
                'q' => $search,
                'student_id' => $selectedStudentId,
            ],
        ]);
    }

    public function store(
        StoreEnrollmentRequest $request,
        EnrollStudent $enroll,
    ): RedirectResponse {
        $data = $request->validated();
        $enroll(
            studentId: (int) $data['student_id'],
            courseId: null,
            actorId: $request->user()->id,
            courseOfferingId: (int) $data['course_offering_id'],
        );

        return back()->with('success', 'Estudiante matriculado correctamente.');
    }

    public function destroy(
        Request $request,
        Enrollment $enrollment,
        UnenrollStudent $unenroll,
    ): RedirectResponse {
        Gate::authorize('enrollments.manage');

        $unenroll($enrollment->id, $request->user()->id);

        return back()->with('success', 'Estudiante desmatriculado correctamente.');
    }

    public function status(
        ChangeEnrollmentStatusRequest $request,
        Enrollment $enrollment,
        ChangeEnrollmentStatus $changeStatus,
    ): RedirectResponse {
        $changeStatus(
            $enrollment->id,
            EnrollmentStatus::from($request->validated('status')),
            $request->user()->id,
        );

        return back()->with('success', 'Estado de inscripción actualizado.');
    }

    /** @return array<string, mixed> */
    private function serializeOffering(CourseOffering $offering): array
    {
        $enrolledCount = (int) ($offering->enrolled_count ?? 0);
        $maxStudents = (int) $offering->max_students;

        return [
            'id' => $offering->id,
            'course_id' => $offering->course_id,
            'group_code' => $offering->group_code,
            'starts_on' => $offering->starts_on?->toDateString(),
            'ends_on' => $offering->ends_on?->toDateString(),
            'modality' => $offering->modality,
            'status' => $offering->effectiveStatus(),
            'max_students' => $maxStudents,
            'enrolled_count' => $enrolledCount,
            'available_spots' => max(0, $maxStudents - $enrolledCount),
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
            ])->values()->all(),
        ];
    }

    /**
     * @param  iterable<Enrollment>  $enrollments
     * @return list<array<string, mixed>>
     */
    private function buildStudentCalendarBlocks(iterable $enrollments): array
    {
        $events = [];
        foreach ($enrollments as $enrollment) {
            $offering = $enrollment->courseOffering;
            if ($offering === null) {
                continue;
            }

            foreach ($offering->sessions->values() as $index => $session) {
                $effectiveStatus = $session->effectiveStatus();
                if ($effectiveStatus === 'cancelled') {
                    continue;
                }

                $events[] = [
                    'id' => $session->id,
                    'scheduleId' => $offering->id,
                    'sessionId' => $session->id,
                    'blockIndex' => $index,
                    'title' => $session->title,
                    'groupCode' => $offering->group_code,
                    'courseName' => $enrollment->course?->name ?? '',
                    'teacherName' => $session->teacher?->name ?? '',
                    'modality' => $offering->modality,
                    'classroomName' => $session->classroom?->name,
                    'meetingUrl' => $session->meeting_url,
                    'sessionDate' => $session->session_date->toDateString(),
                    'dayOfWeek' => (int) $session->session_date->dayOfWeekIso,
                    'startsAt' => $session->starts_at,
                    'endsAt' => $session->ends_at,
                    'status' => $effectiveStatus,
                    'readOnly' => true,
                ];
            }
        }

        return $events;
    }
}
