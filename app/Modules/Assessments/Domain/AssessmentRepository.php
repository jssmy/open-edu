<?php

namespace App\Modules\Assessments\Domain;

interface AssessmentRepository
{
    /**
     * @param array{
     *     title: string,
     *     instructions: array|null,
     *     max_attempts: int,
     *     passing_score: int,
     *     questions: list<array{
     *         statement: array,
     *         points: int,
     *         choices: list<array{text: string, is_correct: bool}>
     *     }>
     * } $attributes
     */
    public function create(int $courseId, array $attributes): int;

    /**
     * @param array{
     *     title: string,
     *     instructions: array|null,
     *     max_attempts: int,
     *     passing_score: int,
     *     questions: list<array{
     *         statement: array,
     *         points: int,
     *         choices: list<array{text: string, is_correct: bool}>
     *     }>
     * } $attributes
     */
    public function update(int $assessmentId, int $expectedVersion, array $attributes): void;

    public function publish(int $assessmentId): void;

    public function delete(int $assessmentId): void;

    /** @return array{id: int, attempt_number: int} */
    public function startAttempt(int $assessmentId, int $studentId): array;

    /**
     * @param  array<int, int>  $answers
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
    public function submitAttempt(int $assessmentId, int $studentId, array $answers): array;

    /**
     * @return array{
     *     assessment_id: int,
     *     assessment_title: string,
     *     attempt_number: int,
     *     score: int,
     *     total_score: int,
     *     passed: bool,
     *     submitted_at: string
     * }|null
     */
    public function latestResult(int $assessmentId, int $studentId): ?array;

    /** @return list<array{student_name: string, attempt_number: int, score: int, total_score: int, passed: bool, submitted_at: string}> */
    public function resultsForAssessment(int $assessmentId): array;

    /** @return list<array{assessment_id: int, assessment_title: string, attempt_number: int, score: int, total_score: int, passed: bool, submitted_at: string}> */
    public function resultsForStudent(int $studentId): array;
}
