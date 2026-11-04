<?php

namespace App\Modules\Scheduling\Domain;

/** Representa una sesión individual programada en una fecha y rango horario concretos. */
final readonly class CourseSession
{
    public function __construct(
        public ?int $id,
        public string $title,
        public string $sessionDate,
        public string $startsAt,
        public string $endsAt,
        public int $startMinutes,
        public int $endMinutes,
        public int $teacherId,
        public ?int $classroomId,
        public ?string $meetingUrl,
        public SessionStatus $status,
        public ?string $courseName = null,
    ) {}

    public static function fromPrimitives(
        string $title,
        string $sessionDate,
        string $startsAt,
        string $endsAt,
        int $teacherId,
        ?int $classroomId = null,
        ?string $meetingUrl = null,
        SessionStatus|string $status = SessionStatus::Scheduled,
        ?int $id = null,
        ?string $courseName = null,
    ): self {
        $startMinutes = self::toMinutes($startsAt);
        $endMinutes = self::toMinutes($endsAt);

        if ($endMinutes <= $startMinutes) {
            throw new SchedulingRuleViolation('sessions', 'La hora de finalización de cada sesión debe ser posterior a la de inicio.');
        }

        $statusEnum = is_string($status) ? SessionStatus::from($status) : $status;

        return new self(
            id: $id,
            title: trim($title) !== '' ? trim($title) : 'Sesión de clase',
            sessionDate: $sessionDate,
            startsAt: $startsAt,
            endsAt: $endsAt,
            startMinutes: $startMinutes,
            endMinutes: $endMinutes,
            teacherId: $teacherId,
            classroomId: $classroomId,
            meetingUrl: $meetingUrl !== null && trim($meetingUrl) !== '' ? trim($meetingUrl) : null,
            status: $statusEnum,
            courseName: $courseName,
        );
    }

    public function overlaps(self $other): bool
    {
        if ($this->status === SessionStatus::Cancelled || $other->status === SessionStatus::Cancelled) {
            return false;
        }

        if ($this->sessionDate !== $other->sessionDate) {
            return false;
        }

        return $this->startMinutes < $other->endMinutes && $this->endMinutes > $other->startMinutes;
    }

    private static function toMinutes(string $time): int
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches)) {
            throw new SchedulingRuleViolation('sessions', "Formato de hora inválido: {$time}.");
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }
}
