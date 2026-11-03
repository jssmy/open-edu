<?php

namespace App\Modules\Reports\Infrastructure;

use App\Modules\Reports\Application\ReportType;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExporter
{
    /**
     * @param  iterable<array<string, string|int|bool|null>>  $rows
     */
    public function download(ReportType $report, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($report, $rows): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('No se pudo abrir la salida del archivo CSV.');
            }

            try {
                fputcsv($stream, array_values($report->headings()), ',', '"', '');

                foreach ($rows as $row) {
                    $cells = array_map(
                        fn (string|int|bool|null $value): string => $this->safeCell($value),
                        array_values($row),
                    );
                    fputcsv($stream, $cells, ',', '"', '');
                }
            } finally {
                fclose($stream);
            }
        }, $report->filename(), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function safeCell(string|int|bool|null $value): string
    {
        $cell = match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Sí' : 'No',
            default => (string) $value,
        };

        if (is_string($value) && preg_match('/^[=+\-@\t\r]/u', $value) === 1) {
            return "'".$cell;
        }

        return $cell;
    }
}
