<?php

namespace App\Modules\Scheduling\Infrastructure;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Domain\CourseSession as DomainSession;
use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SessionStatus;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Scheduling\Infrastructure\Models\CourseSession;
use Illuminate\Support\Facades\DB;

class EloquentScheduleRepository implements ScheduleRepository
{
    /** @return list<DomainSession> */
    public function teacherSessions(int $teacherId, ?int $exceptOfferingId = null, ?int $exceptSessionId = null): array
    {
        return CourseSession::query()
            ->where('teacher_id', $teacherId)
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->when($exceptOfferingId !== null, fn ($q) => $q->where('course_offering_id', '!=', $exceptOfferingId))
            ->when($exceptSessionId !== null, fn ($q) => $q->whereKeyNot($exceptSessionId))
            ->with('offering.course:id,name')
            ->get()
            ->map(fn (CourseSession $s): DomainSession => $this->toDomainSession($s))
            ->all();
    }

    /** @return list<DomainSession> */
    public function classroomSessions(int $classroomId, ?int $exceptOfferingId = null, ?int $exceptSessionId = null): array
    {
        return CourseSession::query()
            ->where('classroom_id', $classroomId)
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->when($exceptOfferingId !== null, fn ($q) => $q->where('course_offering_id', '!=', $exceptOfferingId))
            ->when($exceptSessionId !== null, fn ($q) => $q->whereKeyNot($exceptSessionId))
            ->with('offering.course:id,name')
            ->get()
            ->map(fn (CourseSession $s): DomainSession => $this->toDomainSession($s))
            ->all();
    }

    /** @return list<DomainSession> */
    public function studentActiveSessions(int $studentId, ?int $exceptCourseId = null): array
    {
        return CourseSession::query()
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->whereHas('offering', function ($query) use ($studentId, $exceptCourseId): void {
                $query->when($exceptCourseId !== null, fn ($q) => $q->where('course_id', '!=', $exceptCourseId))
                    ->whereHas('enrollments', function ($e) use ($studentId): void {
                        $e->where('student_id', $studentId)->where('status', 'active');
                    });
            })
            ->with('offering.course:id,name')
            ->get()
            ->map(fn (CourseSession $s): DomainSession => $this->toDomainSession($s))
            ->all();
    }

    public function create(array $data, array $teacherIds, array $sessions): int
    {
        return DB::transaction(function () use ($data, $teacherIds, $sessions): int {
            $offering = CourseOffering::query()->create($data);
            $offering->teachers()->sync($teacherIds);
            foreach ($sessions as $session) {
                $offering->sessions()->create($this->normalizeSessionRow($session));
            }

            return $offering->id;
        });
    }

    public function update(int $offeringId, array $data, array $teacherIds, array $sessions): void
    {
        DB::transaction(function () use ($offeringId, $data, $teacherIds, $sessions): void {
            $offering = CourseOffering::query()->lockForUpdate()->findOrFail($offeringId);
            $offering->update($data);
            $offering->teachers()->sync($teacherIds);
            $offering->sessions()->delete();
            foreach ($sessions as $session) {
                $offering->sessions()->create($this->normalizeSessionRow($session));
            }
        });
    }

    public function delete(int $offeringId): void
    {
        DB::transaction(function () use ($offeringId): void {
            $offering = CourseOffering::query()->findOrFail($offeringId);
            $offering->delete();
        });
    }

    public function saveSession(int $offeringId, array $data, ?int $sessionId = null): int
    {
        return DB::transaction(function () use ($offeringId, $data, $sessionId): int {
            $offering = CourseOffering::query()->lockForUpdate()->findOrFail($offeringId);
            $row = $this->normalizeSessionRow($data);

            if ($sessionId === null) {
                return $offering->sessions()->create($row)->id;
            }

            $session = $offering->sessions()->findOrFail($sessionId);
            $session->update($row);

            return $session->id;
        });
    }

    public function deleteSession(int $sessionId): void
    {
        CourseSession::query()->findOrFail($sessionId)->delete();
    }

    public function findOfferingMeta(int $offeringId): ?array
    {
        $offering = CourseOffering::query()->with('teachers:id')->find($offeringId);
        if ($offering === null) {
            return null;
        }

        return [
            'id' => $offering->id,
            'course_id' => $offering->course_id,
            'modality' => $offering->modality,
            'teacher_ids' => $offering->teachers->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    public function offeringIdOfSession(int $sessionId): int
    {
        return (int) CourseSession::query()->whereKey($sessionId)->value('course_offering_id');
    }

    public function syncCourseTeachers(int $courseId): void
    {
        $teacherIds = DB::table('course_offering_teacher')
            ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_teacher.course_offering_id')
            ->where('course_offerings.course_id', $courseId)
            ->where('course_offerings.status', '!=', 'cancelled')
            ->distinct()
            ->pluck('course_offering_teacher.teacher_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        Course::query()->findOrFail($courseId)->teachers()->sync($teacherIds);
    }

    public function courseIdOf(int $offeringId): int
    {
        return (int) CourseOffering::query()->whereKey($offeringId)->value('course_id');
    }

    public function saveClassroom(array $data, ?int $classroomId = null): int
    {
        if ($classroomId === null) {
            return Classroom::query()->create($data)->id;
        }

        $classroom = Classroom::query()->findOrFail($classroomId);
        $classroom->update($data);

        return $classroom->id;
    }

    public function deleteClassroom(int $classroomId): void
    {
        Classroom::query()->findOrFail($classroomId)->delete();
    }

    public function classroomHasSchedules(int $classroomId): bool
    {
        return CourseSession::query()
            ->where('classroom_id', $classroomId)
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function normalizeSessionRow(array $session): array
    {
        return [
            'title' => trim((string) ($session['title'] ?? '')) !== '' ? trim((string) $session['title']) : 'Sesión de clase',
            'session_date' => $session['session_date'],
            'starts_at' => $session['starts_at'],
            'ends_at' => $session['ends_at'],
            'teacher_id' => (int) $session['teacher_id'],
            'classroom_id' => $session['classroom_id'] ?? null,
            'meeting_url' => $session['meeting_url'] ?? null,
            'status' => $session['status'] ?? SessionStatus::Scheduled->value,
        ];
    }

    private function toDomainSession(CourseSession $session): DomainSession
    {
        return DomainSession::fromPrimitives(
            title: $session->title,
            sessionDate: $session->session_date->toDateString(),
            startsAt: $session->starts_at,
            endsAt: $session->ends_at,
            teacherId: $session->teacher_id,
            classroomId: $session->classroom_id,
            meetingUrl: $session->meeting_url,
            status: $session->effectiveStatus(),
            id: $session->id,
            courseName: $session->offering?->course?->name,
        );
    }
}
