<?php

namespace App\Modules\Assessments\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Modules\Assessments\Application\CreateAssessment;
use App\Modules\Assessments\Application\DeleteAssessment;
use App\Modules\Assessments\Application\GetResults;
use App\Modules\Assessments\Application\PublishAssessment;
use App\Modules\Assessments\Application\StartAttempt;
use App\Modules\Assessments\Application\SubmitAttempt;
use App\Modules\Assessments\Application\UpdateAssessment;
use App\Modules\Assessments\Infrastructure\Http\Requests\SaveAssessmentRequest;
use App\Modules\Assessments\Infrastructure\Http\Requests\SubmitAttemptRequest;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function create(Course $course): Response
    {
        Gate::authorize('manageAssessment', $course);

        return Inertia::render('Assessments/Edit', [
            'course' => ['id' => $course->id, 'name' => $course->name],
            'assessment' => null,
        ]);
    }

    public function edit(Assessment $assessment): Response
    {
        Gate::authorize('manageAssessment', $assessment->course);
        $assessment->load('questions.choices');

        return Inertia::render('Assessments/Edit', [
            'course' => ['id' => $assessment->course_id, 'name' => $assessment->course->name],
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'instructions' => $assessment->instructions,
                'max_attempts' => $assessment->max_attempts,
                'passing_score' => $assessment->passing_score,
                'is_published' => $assessment->is_published,
                'lock_version' => $assessment->lock_version,
                'questions' => $assessment->questions->map(fn ($question): array => [
                    'id' => $question->id,
                    'statement' => $question->statement,
                    'points' => $question->points,
                    'choices' => $question->choices->map(fn ($choice): array => [
                        'id' => $choice->id,
                        'text' => $choice->text,
                        'is_correct' => $choice->is_correct,
                    ])->all(),
                ])->all(),
            ],
        ]);
    }

    public function store(
        SaveAssessmentRequest $request,
        Course $course,
        CreateAssessment $create,
    ): RedirectResponse {
        $assessmentId = $create($course->id, $request->validated());

        return to_route('assessments.edit', $assessmentId)->with('success', 'Evaluación creada.');
    }

    public function update(
        SaveAssessmentRequest $request,
        Assessment $assessment,
        UpdateAssessment $update,
    ): RedirectResponse {
        $attributes = $request->validated();
        $expectedVersion = (int) $attributes['lock_version'];
        unset($attributes['lock_version']);
        $update($assessment->id, $expectedVersion, $attributes);

        return to_route('assessments.edit', $assessment)->with('success', 'Evaluación actualizada.');
    }

    public function destroy(Assessment $assessment, DeleteAssessment $delete): RedirectResponse
    {
        Gate::authorize('manageAssessment', $assessment->course);
        $courseId = $assessment->course_id;
        $delete($assessment->id);

        return to_route('courses.show', $courseId)->with('success', 'Evaluación eliminada.');
    }

    public function publish(Assessment $assessment, PublishAssessment $publish): RedirectResponse
    {
        Gate::authorize('manageAssessment', $assessment->course);
        $publish($assessment->id);

        return back()->with('success', 'Evaluación publicada.');
    }

    public function take(Request $request, Assessment $assessment, StartAttempt $start): Response
    {
        Gate::authorize('take', $assessment);
        $student = Student::query()->where('user_id', $request->user()->id)->firstOrFail();
        $attempt = $start($assessment->id, $student->id);
        $assessment->load('questions.choices');

        return Inertia::render('Assessments/Take', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'instructions' => $assessment->instructions,
                'total_score' => $assessment->total_score,
                'attempt_number' => $attempt['attempt_number'],
                'questions' => $assessment->questions->map(fn ($question): array => [
                    'id' => $question->id,
                    'statement' => $question->statement,
                    'points' => $question->points,
                    'choices' => $question->choices->map(fn ($choice): array => [
                        'id' => $choice->id,
                        'text' => $choice->text,
                    ])->all(),
                ])->all(),
            ],
        ]);
    }

    public function submit(
        SubmitAttemptRequest $request,
        Assessment $assessment,
        SubmitAttempt $submit,
    ): RedirectResponse {
        $student = Student::query()->where('user_id', $request->user()->id)->firstOrFail();
        $answers = array_map(
            fn (int|string $choiceId): int => (int) $choiceId,
            $request->validated('answers'),
        );
        $submit($assessment->id, $student->id, $answers);

        return to_route('assessments.results', $assessment)->with('success', 'Evaluación enviada.');
    }

    public function results(Request $request, Assessment $assessment, GetResults $getResults): Response
    {
        Gate::authorize('viewResults', $assessment);
        $assessment->load('questions');
        $user = $request->user();
        $isStudent = $user->hasRole('estudiante');
        $student = $isStudent
            ? Student::query()->where('user_id', $user->id)->firstOrFail()
            : null;
        $studentId = $student?->id;
        $data = $getResults->forAssessment($assessment->id, $studentId);

        $hasInProgressAttempt = false;
        $canTake = false;

        if ($student !== null) {
            $hasInProgressAttempt = $assessment->attempts()
                ->where('student_id', $student->id)
                ->where('status', 'in_progress')
                ->exists();
            $canTake = Gate::forUser($user)->allows('take', $assessment);
        }

        return Inertia::render('Assessments/Results', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'instructions' => $assessment->instructions,
                'total_score' => $assessment->total_score,
                'questions' => $assessment->questions->map(fn ($question): array => [
                    'id' => $question->id,
                    'statement' => $question->statement,
                ])->all(),
            ],
            'hasInProgressAttempt' => $hasInProgressAttempt,
            'canTake' => $canTake,
            ...$data,
        ]);
    }

    public function myResults(Request $request, GetResults $getResults): Response
    {
        Gate::authorize('study');
        $student = Student::query()->where('user_id', $request->user()->id)->firstOrFail();

        return Inertia::render('Assessments/Results', [
            'assessment' => null,
            'result' => null,
            'results' => $getResults->forStudent($student->id),
        ]);
    }

    public function review(Request $request, Assessment $assessment): Response
    {
        Gate::authorize('viewResults', $assessment);

        $assessment->load([
            'questions.choices',
            'course:id,name',
        ]);

        $isStudent = $request->user()->hasRole('estudiante');

        if ($isStudent) {
            $student = Student::query()->where('user_id', $request->user()->id)->firstOrFail();
        } else {
            $studentId = $request->integer('student_id') ?: null;
            $student = $studentId ? Student::query()->find($studentId) : null;
        }

        $latestAttempt = null;
        $studentAnswers = [];

        if ($student !== null) {
            $latestAttempt = $assessment->attempts()
                ->where('student_id', $student->id)
                ->with('answers')
                ->latest('id')
                ->first();

            if ($latestAttempt) {
                foreach ($latestAttempt->answers as $ans) {
                    $studentAnswers[$ans->question_id] = $ans->choice_id;
                }
            }
        }

        $isCompleted = in_array($latestAttempt?->status, ['completed', 'submitted'], true);

        $questionsData = $assessment->questions->map(function ($question) use ($studentAnswers, $isCompleted): array {
            $selectedChoiceId = $studentAnswers[$question->id] ?? null;
            $selectedChoice = $question->choices->firstWhere('id', $selectedChoiceId);
            $isCorrect = $selectedChoice ? (bool) $selectedChoice->is_correct : false;

            return [
                'id' => $question->id,
                'statement' => $question->statement,
                'points' => $question->points,
                'choices' => $question->choices->map(fn ($choice): array => [
                    'id' => $choice->id,
                    'text' => $choice->text,
                    'is_correct' => $isCompleted ? (bool) $choice->is_correct : null,
                ])->all(),
                'student_answer' => $selectedChoiceId !== null ? [
                    'choice_id' => $selectedChoiceId,
                    'is_correct' => $isCompleted ? $isCorrect : null,
                ] : null,
            ];
        })->all();

        return Inertia::render('Assessments/Review', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'instructions' => $assessment->instructions,
                'total_score' => $assessment->total_score,
                'passing_score' => $assessment->passing_score,
                'course_name' => $assessment->course?->name,
            ],
            'student' => $student ? [
                'id' => $student->id,
                'name' => $student->user?->name ?? 'Estudiante',
                'email' => $student->user?->email,
            ] : null,
            'attempt' => $latestAttempt ? [
                'id' => $latestAttempt->id,
                'attempt_number' => $latestAttempt->attempt_number,
                'status' => $latestAttempt->status,
                'score' => $latestAttempt->score,
                'passed' => $latestAttempt->passed,
                'started_at' => (string) $latestAttempt->started_at,
                'submitted_at' => $latestAttempt->submitted_at ? (string) $latestAttempt->submitted_at : null,
            ] : null,
            'questions' => $questionsData,
            'isStudent' => $isStudent,
            'canContinue' => $isStudent && $latestAttempt?->status === 'in_progress',
        ]);
    }
}
