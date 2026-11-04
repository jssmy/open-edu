<?php

namespace App\Modules\Scheduling\Domain;

enum OfferingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    /**
     * Calcula el estado efectivo según las fechas de inicio y fin cuando no es borrador ni cancelada.
     */
    public function resolveForDates(?string $startsOn, ?string $endsOn, ?string $today = null): self
    {
        if ($this === self::Draft || $this === self::Cancelled) {
            return $this;
        }

        if ($startsOn === null || $endsOn === null) {
            return $this;
        }

        $currentDate = $today ?? date('Y-m-d');

        if ($currentDate < $startsOn) {
            return self::Published;
        }

        if ($currentDate <= $endsOn) {
            return self::InProgress;
        }

        return self::Finished;
    }

    public function isEnrollable(): bool
    {
        return $this === self::Published || $this === self::InProgress;
    }
}
