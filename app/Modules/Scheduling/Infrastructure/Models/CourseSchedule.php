<?php

namespace App\Modules\Scheduling\Infrastructure\Models;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use Database\Factories\Modules\Scheduling\Infrastructure\Models\CourseScheduleFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSchedule extends Model
{
    /** @use HasFactory<CourseScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'teacher_id',
        'academic_period_id',
        'modality',
        'classroom_id',
    ];

    protected static function newFactory(): Factory
    {
        return CourseScheduleFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ScheduleBlock::class, 'course_schedule_id')
            ->orderBy('day_of_week')
            ->orderBy('starts_at');
    }
}

