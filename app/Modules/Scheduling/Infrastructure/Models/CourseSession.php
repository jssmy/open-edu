<?php

namespace App\Modules\Scheduling\Infrastructure\Models;

use App\Models\User;
use App\Modules\Scheduling\Domain\SessionStatus;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSession extends Model
{
    protected $table = 'course_sessions';

    protected $fillable = [
        'course_offering_id',
        'title',
        'session_date',
        'starts_at',
        'ends_at',
        'teacher_id',
        'classroom_id',
        'meeting_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function effectiveStatus(?DateTimeImmutable $now = null): string
    {
        $raw = SessionStatus::tryFrom($this->status) ?? SessionStatus::Scheduled;

        return $raw->resolveForDateTime(
            $this->session_date->toDateString(),
            $this->starts_at,
            $this->ends_at,
            $now,
        )->value;
    }
}
