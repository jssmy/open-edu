<?php

namespace App\Modules\Scheduling\Infrastructure;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\TimeBlock;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;
use App\Modules\Scheduling\Infrastructure\Models\ScheduleBlock;
use Illuminate\Support\Facades\DB;

class EloquentScheduleRepository implements ScheduleRepository
{
    /** @return list<TimeBlock> */
    public function teacherBlocks(int $periodId, int $teacherId, ?int $exceptScheduleId = null): array
    {
        return ScheduleBlock::query()
            ->whereHas('schedule', function ($query) use ($periodId, $teacherId, $exceptScheduleId): void {
                $query->where('academic_period_id', $periodId)
                    ->where('teacher_id', $teacherId)
                    ->when($exceptScheduleId !== null, fn ($q) => $q->whereKeyNot($exceptScheduleId));
            })
            ->get(['day_of_week', 'starts_at', 'ends_at'])
            ->map(fn (ScheduleBlock $block): TimeBlock => TimeBlock::fromTimes(
                $block->day_of_week,
                $block->starts_at,
                $block->ends_at,
            ))
            ->all();
    }

    /** @return list<TimeBlock> */
    public function classroomBlocks(int $periodId, int $classroomId, ?int $exceptScheduleId = null): array
    {
        return ScheduleBlock::query()
            ->whereHas('schedule', function ($query) use ($periodId, $classroomId, $exceptScheduleId): void {
                $query->where('academic_period_id', $periodId)
                    ->where('classroom_id', $classroomId)
                    ->when($exceptScheduleId !== null, fn ($q) => $q->whereKeyNot($exceptScheduleId));
            })
            ->get(['day_of_week', 'starts_at', 'ends_at'])
            ->map(fn (ScheduleBlock $block): TimeBlock => TimeBlock::fromTimes(
                $block->day_of_week,
                $block->starts_at,
                $block->ends_at,
            ))
            ->all();
    }

    public function create(array $data, array $blocks): int
    {
        return DB::transaction(function () use ($data, $blocks): int {
            $schedule = CourseSchedule::query()->create($data);
            $schedule->blocks()->createMany($blocks);

            return $schedule->id;
        });
    }

    public function update(int $scheduleId, array $data, array $blocks): void
    {
        DB::transaction(function () use ($scheduleId, $data, $blocks): void {
            $schedule = CourseSchedule::query()->lockForUpdate()->findOrFail($scheduleId);
            $schedule->update($data);
            $schedule->blocks()->delete();
            $schedule->blocks()->createMany($blocks);
        });
    }

    public function delete(int $scheduleId): void
    {
        DB::transaction(function () use ($scheduleId): void {
            $schedule = CourseSchedule::query()->findOrFail($scheduleId);
            $schedule->delete();
        });
    }

    public function periodHasEnded(int $periodId): bool
    {
        return AcademicPeriod::query()->findOrFail($periodId)->hasEnded();
    }

    public function scheduleEnded(int $scheduleId): bool
    {
        $schedule = CourseSchedule::query()->with('period')->findOrFail($scheduleId);

        return $schedule->period !== null && $schedule->period->hasEnded();
    }

    public function syncCourseTeachers(int $courseId): void
    {
        $teacherIds = CourseSchedule::query()
            ->where('course_id', $courseId)
            ->distinct()
            ->pluck('teacher_id')
            ->all();

        Course::query()->findOrFail($courseId)->teachers()->sync($teacherIds);
    }

    public function courseIdOf(int $scheduleId): int
    {
        return (int) CourseSchedule::query()->whereKey($scheduleId)->value('course_id');
    }

    public function saveClassroom(array $data, ?int $classroomId = null): int
    {
        if ($classroomId === null) {
            return Classroom::query()->create($data)->id;
        }

        $classroom = Classroom::query()->findOrFail($classroomId);
        $classroom->update($data);

        return $classroom->id;
    }

    public function deleteClassroom(int $classroomId): void
    {
        Classroom::query()->findOrFail($classroomId)->delete();
    }

    public function classroomHasSchedules(int $classroomId): bool
    {
        return CourseSchedule::query()->where('classroom_id', $classroomId)->exists();
    }

    public function savePeriod(array $data, ?int $periodId = null): int
    {
        if ($periodId === null) {
            return AcademicPeriod::query()->create($data)->id;
        }

        $period = AcademicPeriod::query()->findOrFail($periodId);
        $period->update($data);

        return $period->id;
    }

    public function deletePeriod(int $periodId): void
    {
        AcademicPeriod::query()->findOrFail($periodId)->delete();
    }

    public function periodHasSchedules(int $periodId): bool
    {
        return CourseSchedule::query()->where('academic_period_id', $periodId)->exists();
    }
}
