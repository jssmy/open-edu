<?php

namespace App\Modules\Scheduling\Domain;

/** Detecta traslapes de bloques; los bloques consecutivos no son conflicto. */
final class ConflictDetector
{
    /**
     * @param  list<TimeBlock>  $proposed
     * @param  list<TimeBlock>  $existing
     */
    public function hasConflict(array $proposed, array $existing): bool
    {
        foreach ($proposed as $candidate) {
            foreach ($existing as $occupied) {
                if ($candidate->overlaps($occupied)) {
                    return true;
                }
            }
        }

        return false;
    }
}
