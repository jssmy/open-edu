<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Modules\Courses\Domain\CourseTargetAudience;
use App\Modules\Courses\Domain\IntroductoryVideo;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Shared\RichText\RichTextDocumentRule;
use App\Shared\RichText\RichTextDocumentValidator;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveCourseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('rich_text_encoded')) {
            // multipart no conserva tipos (números/booleanos/null) del documento Tiptap: llega como JSON.
            $this->merge(collect(['description', 'objectives'])
                ->mapWithKeys(fn (string $field): array => [$field => $this->decodeDocument($this->input($field))])
                ->all());
        }

        $legacyTextFields = [];

        foreach (['description', 'objectives'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $legacyTextFields[$field] = RichTextDocumentValidator::fromPlainText($value);
            } elseif (is_array($value)) {
                $legacyTextFields[$field] = RichTextDocumentValidator::normalizeEmptyImageAlternatives($value);
            }
        }

        if ($this->has('category_id') && $this->input('category_id') === '') {
            $legacyTextFields['category_id'] = null;
        }

        if ($this->has('issues_certificate')) {
            $legacyTextFields['issues_certificate'] = $this->boolean('issues_certificate');
        }

        $this->merge($legacyTextFields);
    }

    private function decodeDocument(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function authorize(): bool
    {
        return $this->user()?->can('courses.manage') ?? false;
    }

    public function rules(): array
    {
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'target_audience' => ['sometimes', 'required', 'string', Rule::enum(CourseTargetAudience::class)],
            'issues_certificate' => ['sometimes', 'boolean'],
            'lock_version' => $this->route('course') instanceof Course
                ? ['required', 'integer', 'min:1']
                : ['prohibited'],
            'description' => ['nullable', 'array', new RichTextDocumentRule($courseId)],
            'objectives' => ['nullable', 'array', new RichTextDocumentRule($courseId)],
            'intro_video_url' => ['nullable', 'string', 'url', 'max:2048'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover' => ['sometimes', 'boolean'],
            'rich_text_encoded' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('intro_video_url')) {
                    return;
                }

                $url = $this->input('intro_video_url');

                if ($url !== null && IntroductoryVideo::fromUrl($url) === null) {
                    $validator->errors()->add(
                        'intro_video_url',
                        'Ingresa una URL HTTPS válida de YouTube o Vimeo.',
                    );
                }

                $course = $this->route('course');
                if ($course instanceof Course && $this->has('category_id')) {
                    $nextCategoryId = $this->input('category_id');
                    $nextRole = $nextCategoryId
                        ? (CourseCategory::query()->whereKey($nextCategoryId)->value('role_name') ?? 'estudiante')
                        : 'estudiante';
                    $currentRole = $course->effectiveRoleName();

                    if ($nextRole !== $currentRole
                        && $course->enrollments()->where('status', EnrollmentStatus::Active->value)->exists()) {
                        $validator->errors()->add(
                            'category_id',
                            'No se puede cambiar la categoría a un rol distinto en un curso que ya tiene matrículas activas.',
                        );
                    }
                }
            },
        ];
    }
}
