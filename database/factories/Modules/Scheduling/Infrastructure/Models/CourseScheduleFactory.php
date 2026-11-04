<?php

namespace Database\Factories\Modules\Scheduling\Infrastructure\Models;

use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;

/**
 * @extends CourseOfferingFactory<CourseSchedule>
 */
class CourseScheduleFactory extends CourseOfferingFactory
{
    protected $model = CourseSchedule::class;
}
