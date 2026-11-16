<?php

namespace App\Modules\Courses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $role_name
 * @property bool $is_active
 */
class CourseCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'role_name',
        'is_active',
    ];

    protected $attributes = [
        'role_name' => 'estudiante',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Course, $this> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}
