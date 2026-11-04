<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ConflictDetector;
use App\Modules\Scheduling\Domain\CourseOffering;
use App\Modules\Scheduling\Domain\CourseSession;
use App\Modules\Scheduling\Domain\Modality;
use App\Modules\Scheduling\Domain\OfferingStatus;
use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;
use App\Modules\Scheduling\Domain\SessionStatus;

/** Crea o actualiza una programación académica (CourseOffering) validando modalidad, docentes y conflictos de sesiones. */
class SaveSchedule
{
    public function __construct(
        private readonly ScheduleRepository $schedules,
        private readonly ConflictDetector $detector,
    ) {}

    /**
     * @param  array{course_id:int,group_code:string,starts_on:string,ends_on:string,max_students:int,modality:string,status:string}  $data
     * @param  list<int>  $teacherIds
     * @param  list<array{title:string,session_date:string,starts_at:string,ends_at:string,teacher_id:int,classroom_id?:?int,meeting_url?:?string,status?:string}>  $rawSessions
     */
    public function __invoke(array $data, array $teacherIds, array $rawSessions, ?int $offeringId = null): int
    {
        $modality = Modality::from($data['modality']);
        $status = OfferingStatus::tryFrom($data['status'] ?? 'published') ?? OfferingStatus::Published;
        $teacherIds = array_values(array_unique(array_map('intval', $teacherIds)));

        $normalizedSessions = [];
        $domainSessions = [];

        foreach ($rawSessions as $raw) {
            $classroomId = $modality === Modality::Virtual
                ? null
                : (isset($raw['classroom_id']) && $raw['classroom_id'] !== null && $raw['classroom_id'] !== '' ? (int) $raw['classroom_id'] : null);

            $meetingUrl = $modality === Modality::InPerson
                ? null
                : (isset($raw['meeting_url']) && trim((string) $raw['meeting_url']) !== '' ? trim((string) $raw['meeting_url']) : null);

            $sessionStatus = SessionStatus::tryFrom((string) ($raw['status'] ?? 'scheduled')) ?? SessionStatus::Scheduled;
            $sessionTeacherId = (int) ($raw['teacher_id'] ?? ($teacherIds[0] ?? 0));

            $domainSession = CourseSession::fromPrimitives(
                title: (string) ($raw['title'] ?? 'Sesión de clase'),
                sessionDate: (string) $raw['session_date'],
                startsAt: (string) $raw['starts_at'],
                endsAt: (string) $raw['ends_at'],
                teacherId: $sessionTeacherId,
                classroomId: $classroomId,
                meetingUrl: $meetingUrl,
                status: $sessionStatus,
            );

            $domainSessions[] = $domainSession;
            $normalizedSessions[] = [
                'title' => $domainSession->title,
                'session_date' => $domainSession->sessionDate,
                'starts_at' => $domainSession->startsAt,
                'ends_at' => $domainSession->endsAt,
                'teacher_id' => $domainSession->teacherId,
                'classroom_id' => $domainSession->classroomId,
                'meeting_url' => $domainSession->meetingUrl,
                'status' => $domainSession->status->value,
            ];
        }

        // Valida invariantes del agregado CourseOffering
        new CourseOffering(
            id: $offeringId,
            courseId: (int) $data['course_id'],
            groupCode: (string) $data['group_code'],
            startsOn: (string) $data['starts_on'],
            endsOn: (string) $data['ends_on'],
            maxStudents: (int) $data['max_students'],
            modality: $modality,
            status: $status,
            teacherIds: $teacherIds,
            sessions: $domainSessions,
        );

        $internalConflictField = $this->detector->hasInternalSessionConflict($domainSessions);
        if ($internalConflictField !== null) {
            throw new SchedulingRuleViolation(
                $internalConflictField,
                $internalConflictField === 'teacher_id'
                    ? 'El docente tiene sesiones que se traslapan dentro de la misma programación.'
                    : 'El aula tiene sesiones que se traslapan dentro de la misma programación.',
            );
        }

        if ($status !== OfferingStatus::Cancelled) {
            foreach ($domainSessions as $session) {
                if ($session->status === SessionStatus::Cancelled) {
                    continue;
                }

                $teacherOccupied = $this->schedules->teacherSessions($session->teacherId, $offeringId);
                if ($this->detector->hasConflict([$session], $teacherOccupied)) {
                    throw new SchedulingRuleViolation('teacher_id', 'El docente ya tiene una sesión programada en esa fecha y horario.');
                }

                if ($session->classroomId !== null) {
                    $classroomOccupied = $this->schedules->classroomSessions($session->classroomId, $offeringId);
                    if ($this->detector->hasConflict([$session], $classroomOccupied)) {
                        throw new SchedulingRuleViolation('classroom_id', 'El aula ya está ocupada por otra sesión en esa fecha y horario.');
                    }
                }
            }
        }

        $payload = [
            'course_id' => (int) $data['course_id'],
            'group_code' => trim((string) $data['group_code']),
            'starts_on' => (string) $data['starts_on'],
            'ends_on' => (string) $data['ends_on'],
            'max_students' => (int) $data['max_students'],
            'modality' => $modality->value,
            'status' => $status->value,
        ];

        if ($offeringId === null) {
            $offeringId = $this->schedules->create($payload, $teacherIds, $normalizedSessions);
        } else {
            $previousCourse = $this->schedules->courseIdOf($offeringId);
            $this->schedules->update($offeringId, $payload, $teacherIds, $normalizedSessions);
            if ($previousCourse !== $payload['course_id']) {
                $this->schedules->syncCourseTeachers($previousCourse);
            }
        }

        $this->schedules->syncCourseTeachers($payload['course_id']);

        return $offeringId;
    }
}
