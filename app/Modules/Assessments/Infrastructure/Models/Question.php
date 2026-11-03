<?php

namespace App\Modules\Assessments\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['assessment_id', 'statement', 'points', 'position'])]
class Question extends Model
{
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return HasMany<Choice, $this> */
    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class);
    }
}
