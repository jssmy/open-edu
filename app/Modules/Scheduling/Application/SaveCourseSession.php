<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ConflictDetector;
use App\Modules\Scheduling\Domain\CourseSession;
use App\Modules\Scheduling\Domain\Modality;
use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;
use App\Modules\Scheduling\Domain\SessionStatus;

/** Crea o actualiza manualmente una sesión individual dentro de un CourseOffering. */
class SaveCourseSession
{
    public function __construct(
        private readonly ScheduleRepository $schedules,
        private readonly ConflictDetector $detector,
    ) {}

    /**
     * @param  array{title:string,session_date:string,starts_at:string,ends_at:string,teacher_id:int,classroom_id?:?int,meeting_url?:?string,status?:string}  $data
     */
    public function __invoke(int $offeringId, array $data, ?int $sessionId = null): int
    {
        $meta = $this->schedules->findOfferingMeta($offeringId);
        if ($meta === null) {
            throw new SchedulingRuleViolation('course_offering_id', 'La programación académica no existe.');
        }

        $modality = Modality::from($meta['modality']);
        $teacherId = (int) $data['teacher_id'];

        if (! in_array($teacherId, $meta['teacher_ids'], true)) {
            throw new SchedulingRuleViolation('teacher_id', 'El docente responsable debe estar habilitado en la programación académica.');
        }

        $classroomId = $modality === Modality::Virtual
            ? null
            : (isset($data['classroom_id']) && $data['classroom_id'] !== null && $data['classroom_id'] !== '' ? (int) $data['classroom_id'] : null);

        if ($modality === Modality::InPerson && $classroomId === null) {
            throw new SchedulingRuleViolation('classroom_id', 'Debe seleccionar un aula para una sesión presencial.');
        }

        $meetingUrl = $modality === Modality::InPerson
            ? null
            : (isset($data['meeting_url']) && trim((string) $data['meeting_url']) !== '' ? trim((string) $data['meeting_url']) : null);

        $status = SessionStatus::tryFrom((string) ($data['status'] ?? 'scheduled')) ?? SessionStatus::Scheduled;

        $session = CourseSession::fromPrimitives(
            title: (string) ($data['title'] ?? 'Sesión de clase'),
            sessionDate: (string) $data['session_date'],
            startsAt: (string) $data['starts_at'],
            endsAt: (string) $data['ends_at'],
            teacherId: $teacherId,
            classroomId: $classroomId,
            meetingUrl: $meetingUrl,
            status: $status,
            id: $sessionId,
        );

        if ($session->status !== SessionStatus::Cancelled) {
            $teacherOccupied = $this->schedules->teacherSessions($session->teacherId, null, $sessionId);
            if ($this->detector->hasConflict([$session], $teacherOccupied)) {
                throw new SchedulingRuleViolation('teacher_id', 'El docente ya tiene una sesión programada en esa fecha y horario.');
            }

            if ($session->classroomId !== null) {
                $classroomOccupied = $this->schedules->classroomSessions($session->classroomId, null, $sessionId);
                if ($this->detector->hasConflict([$session], $classroomOccupied)) {
                    throw new SchedulingRuleViolation('classroom_id', 'El aula ya está ocupada por otra sesión en esa fecha y horario.');
                }
            }
        }

        return $this->schedules->saveSession($offeringId, [
            'title' => $session->title,
            'session_date' => $session->sessionDate,
            'starts_at' => $session->startsAt,
            'ends_at' => $session->endsAt,
            'teacher_id' => $session->teacherId,
            'classroom_id' => $session->classroomId,
            'meeting_url' => $session->meetingUrl,
            'status' => $session->status->value,
        ], $sessionId);
    }
}
