<?php

namespace App\Modules\Enrollments\Infrastructure\Models;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Students\Infrastructure\Models\Student;
use Carbon\CarbonImmutable;
use Database\Factories\Modules\Enrollments\Infrastructure\Models\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $student_id
 * @property int $course_id
 * @property EnrollmentStatus $status
 * @property CarbonImmutable $enrolled_at
 */
#[Fillable(['student_id', 'course_id', 'status', 'enrolled_at'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return EnrollmentFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }
}
