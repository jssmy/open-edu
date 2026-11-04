<?php

namespace App\Modules\Scheduling\Infrastructure\Models;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Scheduling\Domain\OfferingStatus;
use Database\Factories\Modules\Scheduling\Infrastructure\Models\CourseOfferingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseOffering extends Model
{
    /** @use HasFactory<CourseOfferingFactory> */
    use HasFactory;

    protected $table = 'course_offerings';

    protected $fillable = [
        'course_id',
        'group_code',
        'starts_on',
        'ends_on',
        'max_students',
        'modality',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'max_students' => 'integer',
        ];
    }

    protected static function newFactory(): Factory
    {
        return CourseOfferingFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_offering_teacher', 'course_offering_id', 'teacher_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'course_offering_id')
            ->orderBy('session_date')
            ->orderBy('starts_at');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_offering_id');
    }

    public function effectiveStatus(?string $today = null): string
    {
        $raw = OfferingStatus::tryFrom($this->status) ?? OfferingStatus::Published;

        return $raw->resolveForDates(
            $this->starts_on?->toDateString(),
            $this->ends_on?->toDateString(),
            $today,
        )->value;
    }

    public function isEnrollable(?string $today = null): bool
    {
        $raw = OfferingStatus::tryFrom($this->status) ?? OfferingStatus::Published;

        return $raw->resolveForDates(
            $this->starts_on?->toDateString(),
            $this->ends_on?->toDateString(),
            $today,
        )->isEnrollable();
    }

    /**
     * @param  list<string>|null  $existingCodes
     */
    public static function nextGroupCodeForCourse(int $courseId, ?int $ignoreOfferingId = null, ?array $existingCodes = null): string
    {
        if ($courseId <= 0) {
            return 'GRP-01';
        }

        $codes = $existingCodes ?? self::query()
            ->where('course_id', $courseId)
            ->when($ignoreOfferingId !== null, fn ($q) => $q->whereKeyNot($ignoreOfferingId))
            ->pluck('group_code')
            ->all();

        $sequence = count($codes) + 1;
        do {
            $candidate = sprintf('GRP-%02d', $sequence);
            $sequence++;
        } while (in_array($candidate, $codes, true));

        return $candidate;
    }
}
