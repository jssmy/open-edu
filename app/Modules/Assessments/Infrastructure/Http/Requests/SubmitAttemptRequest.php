<?php

namespace App\Modules\Assessments\Infrastructure\Http\Requests;

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        return $assessment instanceof Assessment
            && ($this->user()?->can('submit', $assessment) ?? false);
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'exists:choices,id'],
        ];
    }
}
