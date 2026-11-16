<?php

namespace App\Modules\Progress\Infrastructure\Models;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Infrastructure\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property int $course_id
 * @property int $user_id
 * @property ?int $student_id
 * @property string $code
 * @property string $file_url
 * @property CarbonImmutable $issued_at
 */
class Certificate extends Model
{
    protected $fillable = [
        'enrollment_id',
        'course_id',
        'user_id',
        'student_id',
        'code',
        'file_url',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
