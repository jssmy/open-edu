<?php

namespace App\Modules\Assessments\Infrastructure\Models;

use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'instructions', 'max_attempts', 'passing_score', 'total_score', 'is_published'])]
class Assessment extends Model
{
    protected function casts(): array
    {
        return [
            'instructions' => 'array',
            'max_attempts' => 'integer',
            'passing_score' => 'integer',
            'total_score' => 'integer',
            'is_published' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    /** @return HasMany<Attempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }
}
