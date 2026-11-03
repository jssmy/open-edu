<?php

namespace App\Modules\Reports\Application;

final class GetReports
{
    public function __construct(private readonly ReportRepository $reports) {}

    /**
     * @return array{
     *     course: array{id: int, name: string},
     *     students: list<array<string, mixed>>
     * }
     */
    public function courseProgress(int $courseId): array
    {
        return $this->reports->courseProgress($courseId);
    }

    /** @return list<array<string, mixed>> */
    public function myProgress(int $studentId): array
    {
        return $this->reports->myProgress($studentId);
    }

    /**
     * @param  array<string, int|string|bool>  $filters
     * @return list<array<string, mixed>>
     */
    public function rows(
        ReportType $report,
        array $filters,
        ?int $teacherId,
    ): array {
        return match ($report) {
            ReportType::Enrollments => $this->reports->enrollments($filters, $teacherId),
            ReportType::Progress => $this->reports->progress($filters, $teacherId),
            ReportType::Results => $this->reports->results($filters, $teacherId),
        };
    }

    /**
     * @param  array<string, int|string|bool>  $filters
     * @return iterable<array<string, string|int|bool|null>>
     */
    public function exportRows(
        ReportType $report,
        array $filters,
        ?int $teacherId,
    ): iterable {
        return $this->reports->exportRows($report, $filters, $teacherId);
    }
}
