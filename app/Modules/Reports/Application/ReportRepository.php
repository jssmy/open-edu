<?php

namespace App\Modules\Reports\Application;

interface ReportRepository
{
    /**
     * @return array{
     *     course: array{id: int, name: string},
     *     students: list<array<string, mixed>>
     * }
     */
    public function courseProgress(int $courseId): array;

    /** @return list<array<string, mixed>> */
    public function myProgress(int $studentId): array;

    /**
     * @param  array<string, int|string|bool>  $filters
     * @return list<array<string, mixed>>
     */
    public function enrollments(array $filters, ?int $teacherId): array;

    /**
     * @param  array<string, int|string|bool>  $filters
     * @return list<array<string, mixed>>
     */
    public function progress(array $filters, ?int $teacherId): array;

    /**
     * @param  array<string, int|string|bool>  $filters
     * @return list<array<string, mixed>>
     */
    public function results(array $filters, ?int $teacherId): array;

    /**
     * @param  array<string, int|string|bool>  $filters
     * @return iterable<array<string, string|int|bool|null>>
     */
    public function exportRows(
        ReportType $report,
        array $filters,
        ?int $teacherId,
    ): iterable;
}
