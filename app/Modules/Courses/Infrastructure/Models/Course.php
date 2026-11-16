<?php

namespace App\Modules\Courses\Infrastructure\Models;

use App\Models\User;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Domain\IntroductoryVideo;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Progress\Infrastructure\Models\Certificate;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Database\Factories\Modules\Courses\Infrastructure\Models\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'objectives',
        'intro_video_provider',
        'intro_video_id',
        'status',
        'target_audience',
        'issues_certificate',
        'cover_path',
        'cover_mime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'target_audience' => 'student',
        'issues_certificate' => true,
    ];

    protected static function newFactory(): Factory
    {
        return CourseFactory::new();
    }

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'description' => 'array',
            'objectives' => 'array',
            'issues_certificate' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<CourseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    /**
     * Filtra cursos cuyas categorías están dirigidas al rol estudiante (o cursos legacy sin categoría).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForStudentAudience(Builder $query): Builder
    {
        return $query->where('target_audience', 'student')
            ->where(function (Builder $inner): void {
                $inner->whereNull('category_id')
                    ->orWhereHas('category', fn (Builder $cat) => $cat->where('role_name', 'estudiante'));
            });
    }

    /**
     * Filtra cursos cuyas categorías están dirigidas a alguno de los roles indicados.
     *
     * @param  Builder<self>  $query
     * @param  list<string>|string  $roles
     * @return Builder<self>
     */
    public function scopeForRoles(Builder $query, array|string $roles): Builder
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        return $query->whereHas('category', fn (Builder $cat) => $cat->whereIn('role_name', $roleList));
    }

    public function effectiveRoleName(): string
    {
        if ($this->relationLoaded('category') && $this->category !== null) {
            return $this->category->role_name;
        }

        if ($this->category_id !== null) {
            $roleName = CourseCategory::query()->whereKey($this->category_id)->value('role_name');
            if (is_string($roleName) && $roleName !== '') {
                return $roleName;
            }
        }

        return $this->target_audience === 'teacher' ? 'docente' : 'estudiante';
    }

    /** @return HasMany<Certificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'course_id');
    }

    public function introVideoUrl(): ?string
    {
        return IntroductoryVideo::fromStoredValues(
            $this->intro_video_provider,
            $this->intro_video_id,
        )?->canonicalUrl();
    }

    public function coverUrl(): string
    {
        return route('courses.cover', $this, false).'?v='.($this->updated_at?->timestamp ?? 0);
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

    /** @return HasMany<CourseOffering, $this> */
    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class, 'course_id');
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
