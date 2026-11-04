<?php

namespace Database\Factories\Modules\Scheduling\Infrastructure\Models;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseSchedule>
 */
class CourseScheduleFactory extends Factory
{
    protected $model = CourseSchedule::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'teacher_id' => User::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'modality' => 'virtual',
            'classroom_id' => null,
        ];
    }
}

