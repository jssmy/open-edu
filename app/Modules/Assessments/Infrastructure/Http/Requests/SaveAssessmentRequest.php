<?php

namespace App\Modules\Assessments\Infrastructure\Http\Requests;

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SaveAssessmentRequest extends FormRequest
{
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:65535'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.statement' => ['required', 'string', 'max:10000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'questions.*.choices' => ['required', 'array', 'min:2', 'max:20'],
            'questions.*.choices.*.text' => ['required', 'string', 'max:2000'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
        ];
    }
}
