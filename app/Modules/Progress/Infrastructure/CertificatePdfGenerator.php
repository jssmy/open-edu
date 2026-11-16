<?php

namespace App\Modules\Progress\Infrastructure;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

final class CertificatePdfGenerator
{
    /**
     * Genera el archivo PDF del certificado utilizando la plantilla institucional
     * de la Asociación Un Día de Esperanza y lo almacena en el volumen
     * de archivos, retornando únicamente la URL/ruta de referencia.
     *
     * @param array{
     *     code: string,
     *     participant_name: string,
     *     course_name: string,
     *     category_name?: ?string,
     *     issued_date: string,
     *     ong_name?: string
     * } $data
     */
    public function generateAndStore(array $data): string
    {
        $ongName = $data['ong_name'] ?? config('app.ong_name', 'Asociación Un Día de Esperanza');

        View::make('certificates.template', [
            'ongName' => $ongName,
            'participantName' => $data['participant_name'],
            'courseName' => $data['course_name'],
            'issuedDate' => $data['issued_date'],
            'code' => $data['code'],
        ])->render();

        $pdfBytes = $this->buildLandscapePdf(
            ongName: (string) $ongName,
            participantName: $data['participant_name'],
            courseName: $data['course_name'],
            issuedDate: $data['issued_date'],
            code: $data['code'],
        );

        $relativePath = "certificates/{$data['code']}.pdf";
        Storage::disk('local')->put($relativePath, $pdfBytes);

        return "/storage/{$relativePath}";
    }

    public static function resolveStoragePath(string $fileUrl): string
    {
        $trimmed = ltrim($fileUrl, '/');
        if (str_starts_with($trimmed, 'storage/')) {
            return substr($trimmed, strlen('storage/'));
        }

        return $trimmed;
    }

    private function buildLandscapePdf(
        string $ongName,
        string $participantName,
        string $courseName,
        string $issuedDate,
        string $code,
    ): string {
        $lines = [
            // 1. Fondo cálido marfil (Ivory Canvas #FFFCF7)
            'q',
            '1.00 0.99 0.97 rg',
            '0 0 842 595 re f',
            'Q',

            // 2. Borde exterior institucional Carmesí Esperanza (#BE123C -> 0.75 0.07 0.24)
            'q',
            '0.75 0.07 0.24 RG',
            '4.5 w',
            '28 28 786 539 re S',
            'Q',

            // 3. Marco interior doble Ámbar Dorado (#D97706 -> 0.85 0.47 0.02)
            'q',
            '0.85 0.47 0.02 RG',
            '1.8 w',
            '38 38 766 519 re S',
            '0.8 w',
            '43 43 756 509 re S',
            'Q',

            // 4. Esquinas ornamentales en Carmesí y Dorado
            'q',
            '0.75 0.07 0.24 rg',
            '38 545 26 12 re f',
            '778 545 26 12 re f',
            '38 38 26 12 re f',
            '778 38 26 12 re f',
            'Q',

            // 5. Franja superior institucional (Carmesí + Dorado)
            'q',
            '0.75 0.07 0.24 rg',
            '311 520 110 5 re f',
            '0.96 0.62 0.04 rg',
            '421 520 110 5 re f',
            'Q',

            // 6. Cabecera con el nombre de la ONG
            $this->centeredText(
                $this->toPdfEncoding(mb_strtoupper($ongName, 'UTF-8')),
                488,
                16,
                'F2',
                '0.75 0.07 0.24',
            ),

            // 7. Título Principal del Certificado
            $this->centeredText(
                'CERTIFICADO DE RECONOCIMIENTO',
                432,
                28,
                'F3',
                '0.06 0.09 0.16',
            ),

            // Separador central dorado con diamante
            'q',
            '0.96 0.62 0.04 RG 1.6 w 280 414 m 562 414 l S',
            '0.75 0.07 0.24 rg 417 410 8 8 re f',
            'Q',

            // 8. Texto de otorgamiento y nombre del participante
            $this->centeredText(
                $this->toPdfEncoding("La {$ongName} otorga el presente certificado a:"),
                380,
                13,
                'F1',
                '0.28 0.33 0.41',
            ),
            $this->centeredText(
                $this->toPdfEncoding($participantName),
                332,
                26,
                'F3',
                '0.75 0.07 0.24',
            ),

            // Línea dorada bajo el nombre del participante
            'q',
            '0.85 0.47 0.02 RG 1.4 w 185 316 m 657 316 l S',
            'Q',

            // 9. Curso completado (sin categoría ni texto "Aula Virtual")
            $this->centeredText(
                'Por haber culminado satisfactoriamente el curso:',
                282,
                13,
                'F1',
                '0.20 0.25 0.33',
            ),
            $this->centeredText(
                $this->toPdfEncoding($courseName),
                242,
                mb_strlen($courseName) > 45 ? 18 : 21,
                'F2',
                '0.06 0.09 0.16',
            ),

            // 10. Lema de la ONG
            $this->centeredText(
                $this->toPdfEncoding('"Educando con amor y construyendo futuro en el Perú"'),
                188,
                12,
                'F4',
                '0.39 0.45 0.55',
            ),

            // 11. Pie de certificado: Firma izquierda | Metadatos de verificación derecha (sin sello central)
            'q 0.20 0.25 0.33 RG 1.2 w 90 112 m 270 112 l S Q',
            $this->positionedText(
                $this->toPdfEncoding('Dirección Académica'),
                128,
                96,
                11,
                'F2',
                '0.06 0.09 0.16',
            ),
            $this->positionedText(
                $this->toPdfEncoding($ongName),
                102,
                82,
                9,
                'F1',
                '0.39 0.45 0.55',
            ),

            // Bloque derecho: fecha y código de verificación
            $this->positionedText(
                $this->toPdfEncoding('Fecha de emisión: '.$issuedDate),
                560,
                102,
                10,
                'F2',
                '0.20 0.25 0.33',
            ),
            $this->positionedText(
                $this->toPdfEncoding('Código: '.$code),
                560,
                86,
                10,
                'F2',
                '0.75 0.07 0.24',
            ),
        ];

        $contentStream = implode("\n", $lines);
        $streamLength = strlen($contentStream);

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >> >> /Contents 8 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>',
            7 => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>',
            8 => "<< /Length {$streamLength} >>\nstream\n{$contentStream}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function centeredText(string $text, int $y, int $fontSize, string $fontKey, string $rgb): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $approxWidth = strlen($text) * ($fontSize * 0.50);
        $x = max(55, (int) round((842 - $approxWidth) / 2));

        return "BT /{$fontKey} {$fontSize} Tf {$rgb} rg {$x} {$y} Td ({$escaped}) Tj ET";
    }

    private function positionedText(string $text, int $x, int $y, int $fontSize, string $fontKey, string $rgb): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);

        return "BT /{$fontKey} {$fontSize} Tf {$rgb} rg {$x} {$y} Td ({$escaped}) Tj ET";
    }

    private function toPdfEncoding(string $value): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return is_string($converted) && $converted !== '' ? $converted : $value;
    }
}
