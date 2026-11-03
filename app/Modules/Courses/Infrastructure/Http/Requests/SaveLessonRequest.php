<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('content.manage') ?? false;
    }

    public function rules(): array
    {
        $type = ContentType::tryFrom((string) $this->input('content_type'));
        $isUpdate = $this->route('lesson') instanceof Lesson;
        $fileIsRequired = ! $isUpdate && (
            in_array($type, [ContentType::Document, ContentType::Image], true)
            || ($type === ContentType::Video && ! $this->filled('content'))
        );
        $contentIsRequired = in_array($type, [ContentType::Text, ContentType::Link], true)
            || ($type === ContentType::Video && ! $this->hasFile('file') && ! $isUpdate);

        $fileTypes = match ($type) {
            ContentType::Document => ['pdf', 'doc', 'docx', 'ppt', 'pptx'],
            ContentType::Image => ['jpg', 'jpeg', 'png', 'webp'],
            ContentType::Video => ['mp4', 'webm', 'mov'],
            default => [],
        };

        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'content' => [Rule::requiredIf($contentIsRequired), 'nullable', 'string', 'max:10000'],
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
