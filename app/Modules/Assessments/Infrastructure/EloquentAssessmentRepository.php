<?php

namespace App\Modules\Assessments\Infrastructure;

use App\Modules\Assessments\Domain\Assessment as AssessmentRules;
use App\Modules\Assessments\Domain\AssessmentRepository;
use App\Modules\Assessments\Domain\Attempt as AttemptRules;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Attempt;
use App\Modules\Assessments\Infrastructure\Models\Choice;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EloquentAssessmentRepository implements AssessmentRepository
{
    public function create(int $courseId, array $attributes): int
    {
        return DB::transaction(function () use ($courseId, $attributes): int {
            $assessment = Assessment::query()->create([
                'course_id' => $courseId,
                'title' => $attributes['title'],
                'max_attempts' => $attributes['max_attempts'],
                'passing_score' => $attributes['passing_score'],
                'total_score' => array_sum(array_column($attributes['questions'], 'points')),
                'is_published' => false,
            ]);

            $this->createQuestions($assessment, $attributes['questions']);

            return $assessment->id;
        });
    }

    public function update(int $assessmentId, array $attributes): void
    {
        DB::transaction(function () use ($assessmentId, $attributes): void {
            $assessment = Assessment::query()->lockForUpdate()->findOrFail($assessmentId);

            if ($assessment->attempts()->exists()) {
                throw new DomainException(
                    'No se puede editar una evaluación que ya tiene intentos registrados.',
                    'assessment',
                );
            }

            $assessment->update([
                'title' => $attributes['title'],
                'max_attempts' => $attributes['max_attempts'],
                'passing_score' => $attributes['passing_score'],
                'total_score' => array_sum(array_column($attributes['questions'], 'points')),
                'is_published' => false,
            ]);
            $assessment->questions()->delete();
            $this->createQuestions($assessment, $attributes['questions']);
        });
    }

    public function publish(int $assessmentId): void
    {
        DB::transaction(function () use ($assessmentId): void {
            $assessment = Assessment::query()
                ->with('questions.choices')
                ->lockForUpdate()
                ->findOrFail($assessmentId);
            $questions = $assessment->questions->map(fn (Question $question): array => [
                'statement' => $question->statement,
                'points' => $question->points,
                'choices' => $question->choices->map(fn (Choice $choice): array => [
                    'text' => $choice->text,
                    'is_correct' => $choice->is_correct,
                ])->all(),
            ])->all();
            $totalScore = array_sum(array_column($questions, 'points'));
            $rules = new AssessmentRules(
                $assessment->max_attempts,
                $assessment->passing_score,
                $totalScore,
            );
            $rules->assertPublishable($questions);

            $assessment->update([
                'total_score' => $totalScore,
                'is_published' => true,
            ]);
        });
    }

    public function delete(int $assessmentId): void
    {
        DB::transaction(function () use ($assessmentId): void {
            $assessment = Assessment::query()->lockForUpdate()->findOrFail($assessmentId);

            if ($assessment->attempts()->exists()) {
                throw new DomainException(
                    'No se puede eliminar una evaluación que ya tiene intentos registrados.',
                    'assessment',
                );
            }

            $assessment->delete();
        });
    }

    public function startAttempt(int $assessmentId, int $studentId): array
    {
        return DB::transaction(function () use ($assessmentId, $studentId): array {
            $assessment = Assessment::query()
                ->lockForUpdate()
                ->findOrFail($assessmentId);
            $course = Course::query()->withTrashed()->lockForUpdate()->findOrFail($assessment->course_id);

            if (! $assessment->is_published || $course->trashed() || $course->status !== 'published') {
                throw new DomainException('La evaluación no está disponible.', 'assessment');
            }

            $this->assertActiveEnrollment($studentId, $assessment->course_id);

            $activeAttempt = $assessment->attempts()
                ->where('student_id', $studentId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if ($activeAttempt !== null) {
                return [
                    'id' => $activeAttempt->id,
                    'attempt_number' => $activeAttempt->attempt_number,
                ];
            }

            $attemptsTaken = $assessment->attempts()
                ->where('student_id', $studentId)
                ->count();
            $rules = new AssessmentRules(
                $assessment->max_attempts,
                $assessment->passing_score,
                $assessment->total_score,
            );
            $attemptNumber = $rules->nextAttemptNumber($attemptsTaken);
            new AttemptRules($attemptNumber, $assessment->max_attempts);

            $attempt = $assessment->attempts()->create([
                'student_id' => $studentId,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            return [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
            ];
        });
    }

    public function submitAttempt(int $assessmentId, int $studentId, array $answers): array
    {
        return DB::transaction(function () use ($assessmentId, $studentId, $answers): array {
            $assessment = Assessment::query()
                ->with('questions.choices')
                ->findOrFail($assessmentId);
            $attempt = $assessment->attempts()
                ->where('student_id', $studentId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertActiveEnrollment($studentId, $assessment->course_id);

            $questions = $assessment->questions;
            if (count($answers) !== $questions->count()) {
                throw new DomainException('Selecciona una alternativa para cada pregunta.', 'answers');
            }

            $gradeAnswers = [];
            $selectedChoices = [];
            foreach ($questions as $question) {
                $choiceId = $answers[$question->id] ?? null;
                $choice = $question->choices->firstWhere('id', $choiceId);

                if ($choice === null) {
                    throw new DomainException('La alternativa seleccionada no pertenece a la pregunta.', 'answers');
                }

                $selectedChoices[] = [
                    'question_id' => $question->id,
                    'choice_id' => $choice->id,
                ];
                $gradeAnswers[] = [
                    'points' => $question->points,
                    'is_correct' => $choice->is_correct,
                ];
            }

            $rules = new AssessmentRules(
                $assessment->max_attempts,
                $assessment->passing_score,
                $assessment->total_score,
            );
            $grade = $rules->grade($gradeAnswers);
            foreach ($selectedChoices as $selectedChoice) {
                $attempt->answers()->create($selectedChoice);
            }

            $attempt->update([
                'score' => $grade['score'],
                'passed' => $grade['passed'],
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return $this->resultData($attempt->fresh('assessment'));
        });
    }

    public function latestResult(int $assessmentId, int $studentId): ?array
    {
        $attempt = Attempt::query()
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('status', 'submitted')
            ->with('assessment')
            ->orderByDesc('attempt_number')
            ->first();

        return $attempt === null ? null : $this->resultData($attempt);
    }

    public function resultsForAssessment(int $assessmentId): array
    {
        /** @var Collection<int, Attempt> $attempts */
        $attempts = Attempt::query()
            ->where('assessment_id', $assessmentId)
            ->where('status', 'submitted')
            ->with(['student.user', 'assessment'])
            ->orderBy('student_id')
            ->orderByDesc('attempt_number')
            ->get()
            ->unique('student_id')
            ->values();

        return $attempts->map(fn (Attempt $attempt): array => [
            'student_name' => $attempt->student->user->name,
            'attempt_number' => $attempt->attempt_number,
            'score' => $attempt->score,
            'total_score' => $attempt->assessment->total_score,
            'passed' => $attempt->passed,
            'submitted_at' => $this->submittedAt($attempt),
        ])->all();
    }

    public function resultsForStudent(int $studentId): array
    {
        /** @var Collection<int, Attempt> $attempts */
        $attempts = Attempt::query()
            ->where('student_id', $studentId)
            ->where('status', 'submitted')
            ->with('assessment')
            ->orderByDesc('submitted_at')
            ->get()
            ->unique('assessment_id')
            ->values();

        return $attempts->map(fn (Attempt $attempt): array => $this->resultData($attempt))->all();
    }

    /**
     * @param list<array{
     *     statement: string,
     *     points: int,
     *     choices: list<array{text: string, is_correct: bool}>
     * }> $questions
     */
    private function createQuestions(Assessment $assessment, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
            $question = $assessment->questions()->create([
                'statement' => $questionData['statement'],
                'points' => $questionData['points'],
                'position' => $index + 1,
            ]);
            $question->choices()->createMany($questionData['choices']);
        }
    }

    private function assertActiveEnrollment(int $studentId, int $courseId): void
    {
        $studentIsActive = Student::query()
            ->whereKey($studentId)
            ->where('is_active', true)
            ->exists();
        $hasActiveEnrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('status', EnrollmentStatus::Active->value)
            ->exists();

        if (! $studentIsActive || ! $hasActiveEnrollment) {
            throw new DomainException('Se requiere una inscripción activa para rendir la evaluación.', 'assessment');
        }
    }

    /**
     * @return array{
     *     assessment_id: int,
     *     assessment_title: string,
     *     attempt_number: int,
     *     score: int,
     *     total_score: int,
     *     passed: bool,
     *     submitted_at: string
     * }
     */
    private function resultData(Attempt $attempt): array
    {
        return [
            'assessment_id' => $attempt->assessment_id,
            'assessment_title' => $attempt->assessment->title,
            'attempt_number' => $attempt->attempt_number,
            'score' => $attempt->score,
            'total_score' => $attempt->assessment->total_score,
            'passed' => $attempt->passed,
            'submitted_at' => $this->submittedAt($attempt),
        ];
    }

    private function submittedAt(Attempt $attempt): string
    {
        if ($attempt->submitted_at === null) {
            throw new LogicException('Un intento enviado debe tener fecha de envío.');
        }

        return $attempt->submitted_at->toIso8601String();
    }
}
