<?php

namespace Database\Factories\Modules\Scheduling\Infrastructure\Models;

use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'name' => 'Aula '.fake()->unique()->numerify('###'),
            'capacity' => 30,
            'is_active' => true,
        ];
    }
}
