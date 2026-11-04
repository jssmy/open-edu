<?php

namespace Database\Factories\Modules\Scheduling\Infrastructure\Models;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOffering>
 */
class CourseOfferingFactory extends Factory
{
    protected $model = CourseOffering::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'group_code' => 'GRP-'.$this->faker->unique()->numerify('###'),
            'starts_on' => now()->subDays(5)->toDateString(),
            'ends_on' => now()->addMonths(3)->toDateString(),
            'max_students' => 30,
            'modality' => 'virtual',
            'status' => 'published',
        ];
    }
}
