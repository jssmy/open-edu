<?php

namespace App\Modules\Assessments\Infrastructure\Http\Requests;

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Shared\RichText\RichTextDocumentRule;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SaveAssessmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_string($this->input('instructions'))) {
            $data['instructions'] = RichTextDocumentValidator::fromPlainText($this->input('instructions'));
        } elseif (is_array($this->input('instructions'))) {
            $data['instructions'] = RichTextDocumentValidator::normalizeEmptyImageAlternatives(
                $this->input('instructions'),
            );
        }

        $questions = $this->input('questions');
        if (is_array($questions)) {
            foreach ($questions as $index => $question) {
                if (is_array($question) && is_string($question['statement'] ?? null)) {
                    $questions[$index]['statement'] = RichTextDocumentValidator::fromPlainText(
                        $question['statement'],
                    );
                } elseif (is_array($question['statement'] ?? null)) {
                    $questions[$index]['statement'] = RichTextDocumentValidator::normalizeEmptyImageAlternatives(
                        $question['statement'],
                    );
                }
            }
            $data['questions'] = $questions;
        }

        $this->merge($data);
    }

    public function authorize(): bool
    {
        $resource = $this->route('assessment') ?? $this->route('course');

        if ($resource instanceof Assessment) {
            $resource = $resource->course;
        }

        if (! $resource instanceof Course || $this->user() === null) {
            return false;
        }

        Gate::forUser($this->user())->authorize('manageAssessment', $resource);

        return true;
    }

    public function rules(): array
    {
        $resource = $this->route('assessment') ?? $this->route('course');
        $courseId = $resource instanceof Assessment
            ? $resource->course_id
            : ($resource instanceof Course ? $resource->id : null);

        return [
            'title' => ['required', 'string', 'max:255'],
            'lock_version' => $this->route('assessment') instanceof Assessment
                ? ['required', 'integer', 'min:1']
                : ['prohibited'],
            'instructions' => ['nullable', 'array', new RichTextDocumentRule($courseId)],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:65535'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.statement' => ['required', 'array', new RichTextDocumentRule($courseId)],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'questions.*.choices' => ['required', 'array', 'min:2', 'max:20'],
            'questions.*.choices.*.text' => ['required', 'string', 'max:2000'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
        ];
    }
}
