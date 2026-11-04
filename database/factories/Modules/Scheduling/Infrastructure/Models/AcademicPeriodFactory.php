<?php

namespace Database\Factories\Modules\Scheduling\Infrastructure\Models;

use App\Modules\Scheduling\Infrastructure\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    public function definition(): array
    {
        return [
            'name' => 'Periodo '.fake()->unique()->numerify('2026-##'),
            'starts_on' => now()->subDays(10)->toDateString(),
            'ends_on' => now()->addMonths(4)->toDateString(),
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (): array => [
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->subDays(5)->toDateString(),
        ]);
    }
}

