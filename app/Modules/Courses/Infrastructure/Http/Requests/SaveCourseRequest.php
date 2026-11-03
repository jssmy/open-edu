<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Modules\Courses\Domain\IntroductoryVideo;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Shared\RichText\RichTextDocumentRule;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveCourseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $legacyTextFields = [];

        foreach (['description', 'objectives'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $legacyTextFields[$field] = RichTextDocumentValidator::fromPlainText($value);
            } elseif (is_array($value)) {
                $legacyTextFields[$field] = RichTextDocumentValidator::normalizeEmptyImageAlternatives($value);
            }
        }

        $this->merge($legacyTextFields);
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
            'lock_version' => $this->route('course') instanceof Course
                ? ['required', 'integer', 'min:1']
                : ['prohibited'],
            'description' => ['nullable', 'array', new RichTextDocumentRule($courseId)],
            'objectives' => ['nullable', 'array', new RichTextDocumentRule($courseId)],
            'intro_video_url' => ['nullable', 'string', 'url', 'max:2048'],
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
            },
        ];
    }
}
