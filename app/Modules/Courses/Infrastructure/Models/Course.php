<?php

namespace App\Modules\Courses\Infrastructure\Models;

use App\Models\User;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Domain\IntroductoryVideo;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use Database\Factories\Modules\Courses\Infrastructure\Models\CourseFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'objectives',
        'intro_video_provider',
        'intro_video_id',
        'status',
    ];

    protected $attributes = ['status' => 'draft'];

    protected static function newFactory(): Factory
    {
        return CourseFactory::new();
    }

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'objectives' => 'array',
            'lock_version' => 'integer',
        ];
    }

    public function introVideoUrl(): ?string
    {
        return IntroductoryVideo::fromStoredValues(
            $this->intro_video_provider,
            $this->intro_video_id,
        )?->canonicalUrl();
    }

    public function introVideoEmbedUrl(): ?string
    {
        return IntroductoryVideo::fromStoredValues(
            $this->intro_video_provider,
            $this->intro_video_id,
        )?->embedUrl();
    }

    /** @return HasMany<Unit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('position');
    }

    /** @return BelongsToMany<User, $this> */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_teacher')->withTimestamps();
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<Assessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /** @return HasMany<CourseMediaImage, $this> */
    public function mediaImages(): HasMany
    {
        return $this->hasMany(CourseMediaImage::class);
    }
}
