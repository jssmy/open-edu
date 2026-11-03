<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Shared\RichText\RichTextDocumentRule;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLessonRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('content_type') !== ContentType::Text->value) {
            return;
        }

        $content = $this->input('content');
        if (is_string($content)) {
            $this->merge(['content' => RichTextDocumentValidator::fromPlainText($content)]);
        } elseif (is_array($content)) {
            $this->merge([
                'content' => RichTextDocumentValidator::normalizeEmptyImageAlternatives($content),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('content.manage') ?? false;
    }

    public function rules(): array
    {
        $type = ContentType::tryFrom((string) $this->input('content_type'));
        $isUpdate = $this->route('lesson') instanceof Lesson;
        $routeResource = $this->route('lesson') ?? $this->route('unit');
        $courseId = match (true) {
            $routeResource instanceof Lesson => $routeResource->unit?->course_id,
            $routeResource instanceof Unit => $routeResource->course_id,
            $routeResource instanceof Course => $routeResource->id,
            default => null,
        };
        $fileIsRequired = ! $isUpdate && (
            in_array($type, [ContentType::Document, ContentType::Image], true)
        );
        $contentIsRequired = in_array($type, [ContentType::Text, ContentType::Link], true)
            || $type === ContentType::Video;

        $fileTypes = match ($type) {
            ContentType::Document => ['pdf', 'doc', 'docx', 'ppt', 'pptx'],
            ContentType::Image => ['jpg', 'jpeg', 'png', 'webp'],
            default => [],
        };
        $contentRules = match ($type) {
            ContentType::Text => ['array', new RichTextDocumentRule($courseId)],
            ContentType::Video => ['url:https', 'max:2048'],
            default => ['string', 'max:10000'],
        };

        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'lock_version' => $isUpdate ? ['required', 'integer', 'min:1'] : ['prohibited'],
            'content' => [
                Rule::requiredIf($contentIsRequired),
                'nullable',
                ...$contentRules,
            ],
            'file' => [
                Rule::requiredIf($fileIsRequired),
                'nullable',
                'file',
                'max:'.config('courses.max_upload_kb', 10240),
                ...($fileTypes === [] ? ['prohibited'] : [
                    'mimes:'.implode(',', $fileTypes),
                    'extensions:'.implode(',', $fileTypes),
                ]),
            ],
        ];
    }
}
