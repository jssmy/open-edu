<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;

class ManagePeriods
{
    public function __construct(private readonly ScheduleRepository $schedules) {}

    /** @param array{name:string,starts_on:string,ends_on:string} $data */
    public function save(array $data, ?int $periodId = null): int
    {
        if ($data['ends_on'] <= $data['starts_on']) {
            throw new SchedulingRuleViolation('ends_on', 'La fecha de fin debe ser posterior a la fecha de inicio.');
        }

        if ($periodId !== null && $this->schedules->periodHasEnded($periodId)) {
            throw new SchedulingRuleViolation('academic_period_id', 'El periodo está cerrado y no admite cambios.');
        }

        return $this->schedules->savePeriod($data, $periodId);
    }

    public function delete(int $periodId): void
    {
        if ($this->schedules->periodHasEnded($periodId)) {
            throw new SchedulingRuleViolation('academic_period_id', 'El periodo está cerrado y no admite cambios.');
        }

        if ($this->schedules->periodHasSchedules($periodId)) {
            throw new SchedulingRuleViolation('period', 'El periodo tiene programaciones asociadas y no puede eliminarse.');
        }

        $this->schedules->deletePeriod($periodId);
    }
}

