<?php

namespace App\Modules\Scheduling\Domain;

use DateTimeImmutable;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    /**
     * Calcula el estado efectivo de una sesión según su fecha y hora de inicio/fin cuando no está cancelada.
     */
    public function resolveForDateTime(
        string $sessionDate,
        string $startsAt,
        string $endsAt,
        ?DateTimeImmutable $now = null,
    ): self {
        if ($this === self::Cancelled) {
            return self::Cancelled;
        }

        $current = $now ?? new DateTimeImmutable;
        $currentDate = $current->format('Y-m-d');
        $currentTime = $current->format('H:i');

        if ($currentDate < $sessionDate) {
            return self::Scheduled;
        }

        if ($currentDate > $sessionDate) {
            return self::Finished;
        }

        if ($currentTime < $startsAt) {
            return self::Scheduled;
        }

        if ($currentTime <= $endsAt) {
            return self::InProgress;
        }

        return self::Finished;
    }
}
