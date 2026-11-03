<?php

namespace Database\Factories\Modules\Courses\Infrastructure\Models;

use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'objectives' => fake()->paragraph(),
            'status' => 'draft',
        ];
    }
}
