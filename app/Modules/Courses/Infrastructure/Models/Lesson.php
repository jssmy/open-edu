<?php

namespace App\Modules\Courses\Infrastructure\Models;

use Database\Factories\Modules\Courses\Infrastructure\Models\LessonFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = ['unit_id', 'title', 'position', 'content_type', 'content', 'is_archived'];

    protected static function newFactory(): Factory
    {
        return LessonFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_archived' => 'boolean',
            'content' => 'array',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
