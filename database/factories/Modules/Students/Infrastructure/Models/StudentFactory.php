<?php

namespace Database\Factories\Modules\Students\Infrastructure\Models;

use App\Models\User;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'identifier' => fake()->unique()->bothify('ST-####'),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
