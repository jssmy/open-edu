<?php

namespace App\Modules\Scheduling\Domain;

/** Detecta traslapes de sesiones por fecha exacta (ignorando sesiones canceladas); sesiones consecutivas no son conflicto. */
final class ConflictDetector
{
    /**
     * @param  list<CourseSession|TimeBlock>  $proposed
     * @param  list<CourseSession|TimeBlock>  $existing
     */
    public function hasConflict(array $proposed, array $existing): bool
    {
        return $this->findConflict($proposed, $existing) !== null;
    }

    /**
     * @param  list<CourseSession|TimeBlock>  $proposed
     * @param  list<CourseSession|TimeBlock>  $existing
     * @return array{proposed: CourseSession|TimeBlock, existing: CourseSession|TimeBlock}|null
     */
    public function findConflict(array $proposed, array $existing): ?array
    {
        foreach ($proposed as $candidate) {
            foreach ($existing as $occupied) {
                if ($candidate instanceof CourseSession && $occupied instanceof CourseSession) {
                    if ($candidate->overlaps($occupied)) {
                        return ['proposed' => $candidate, 'existing' => $occupied];
                    }
                } elseif ($candidate instanceof TimeBlock && $occupied instanceof TimeBlock) {
                    if ($candidate->overlaps($occupied)) {
                        return ['proposed' => $candidate, 'existing' => $occupied];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Verifica si dentro de una misma lista de sesiones propuestas hay traslapes internos para el mismo docente o aula.
     *
     * @param  list<CourseSession>  $sessions
     */
    public function hasInternalSessionConflict(array $sessions): ?string
    {
        $count = count($sessions);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if (! $sessions[$i]->overlaps($sessions[$j])) {
                    continue;
                }

                if ($sessions[$i]->teacherId === $sessions[$j]->teacherId) {
                    return 'teacher_id';
                }

                if ($sessions[$i]->classroomId !== null && $sessions[$i]->classroomId === $sessions[$j]->classroomId) {
                    return 'classroom_id';
                }
            }
        }

        return null;
    }
}
