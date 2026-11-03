<?php

namespace App\Modules\Assessments\Infrastructure\Models;

use App\Modules\Students\Infrastructure\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $submitted_at
 */
#[Fillable([
    'assessment_id',
    'student_id',
    'attempt_number',
    'score',
    'passed',
    'status',
    'started_at',
    'submitted_at',
])]
class Attempt extends Model
{
    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'score' => 'integer',
            'passed' => 'boolean',
            'started_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return HasMany<AttemptAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
