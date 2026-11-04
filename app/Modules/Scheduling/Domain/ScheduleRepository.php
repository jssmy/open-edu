<?php

namespace App\Modules\Scheduling\Domain;

interface ScheduleRepository
{
    /** @return list<TimeBlock> */
    public function teacherBlocks(int $periodId, int $teacherId, ?int $exceptScheduleId = null): array;

    /** @return list<TimeBlock> */
    public function classroomBlocks(int $periodId, int $classroomId, ?int $exceptScheduleId = null): array;

    /** @param array{course_id:int,teacher_id:int,academic_period_id:int,modality:string,classroom_id:?int} $data */
    public function create(array $data, array $blocks): int;

    public function update(int $scheduleId, array $data, array $blocks): void;

    public function delete(int $scheduleId): void;

    public function periodHasEnded(int $periodId): bool;

    public function scheduleEnded(int $scheduleId): bool;

    public function syncCourseTeachers(int $courseId): void;

    public function courseIdOf(int $scheduleId): int;

    /** @param array{name:string,capacity:?int,is_active:bool} $data */
    public function saveClassroom(array $data, ?int $classroomId = null): int;

    public function deleteClassroom(int $classroomId): void;

    public function classroomHasSchedules(int $classroomId): bool;

    /** @param array{name:string,starts_on:string,ends_on:string} $data */
    public function savePeriod(array $data, ?int $periodId = null): int;

    public function deletePeriod(int $periodId): void;

    public function periodHasSchedules(int $periodId): bool;
}
