<?php

namespace App\Modules\Scheduling\Infrastructure\Models;

use Database\Factories\Modules\Scheduling\Infrastructure\Models\ClassroomFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'is_active',
    ];

    protected static function newFactory(): Factory
    {
        return ClassroomFactory::new();
    }

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'classroom_id');
    }
}
