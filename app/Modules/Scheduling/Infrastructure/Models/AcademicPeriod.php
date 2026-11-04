<?php

namespace App\Modules\Scheduling\Infrastructure\Models;

use Database\Factories\Modules\Scheduling\Infrastructure\Models\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    /** @use HasFactory<AcademicPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
    ];

    protected static function newFactory(): Factory
    {
        return AcademicPeriodFactory::new();
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'academic_period_id');
    }

    public function hasEnded(): bool
    {
        return $this->ends_on->copy()->endOfDay()->isPast();
    }
}

