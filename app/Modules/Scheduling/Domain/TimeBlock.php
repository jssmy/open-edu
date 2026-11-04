<?php

namespace App\Modules\Scheduling\Domain;

/** Bloque semanal de horario: día ISO (1–7) y minutos desde medianoche. */
final class TimeBlock
{
    public function __construct(
        public readonly int $dayOfWeek,
        public readonly int $startMinutes,
        public readonly int $endMinutes,
    ) {
        if ($endMinutes <= $startMinutes) {
            throw new SchedulingRuleViolation('blocks', 'La hora de fin debe ser posterior a la de inicio.');
        }
    }

    public static function fromTimes(int $dayOfWeek, string $start, string $end): self
    {
        return new self($dayOfWeek, self::toMinutes($start), self::toMinutes($end));
    }

    public function overlaps(self $other): bool
    {
        return $this->dayOfWeek === $other->dayOfWeek
            && $this->startMinutes < $other->endMinutes
            && $this->endMinutes > $other->startMinutes;
    }

    private static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
