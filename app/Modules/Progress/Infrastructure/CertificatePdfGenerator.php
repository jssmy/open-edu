<?php

namespace App\Modules\Progress\Infrastructure;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

final class CertificatePdfGenerator
{
    /**
     * Tablas métricas de caracteres estándar Adobe Type 1 (unidades por cada 1000 em)
     * para calcular con precisión matemática milimétrica el ancho de los textos
     * y garantizar su centrado horizontal perfecto en la página (X = 421 pt).
     *
     * @var array<string, array<string, int>>
     */
    private static array $fontWidths = [
        'F1' => [ // Helvetica
            ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191,
            '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
            '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
            ':' => 278, ';' => 278, '<' => 584, '=' => 584, '>' => 584, '?' => 556, '@' => 1015,
            'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722,
            'I' => 278, 'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667,
            'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667,
            'Y' => 667, 'Z' => 611, '[' => 278, '\\' => 278, ']' => 278, '^' => 469, '_' => 556, '`' => 222,
            'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556, 'h' => 556,
            'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556,
            'q' => 556, 'r' => 333, 's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500,
            'y' => 500, 'z' => 500, '{' => 334, '|' => 260, '}' => 334, '~' => 584,
        ],
        'F2' => [ // Helvetica-Bold
            ' ' => 278, '!' => 333, '"' => 474, '#' => 556, '$' => 556, '%' => 889, '&' => 722, "'" => 238,
            '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
            '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
            ':' => 333, ';' => 333, '<' => 584, '=' => 584, '>' => 584, '?' => 611, '@' => 975,
            'A' => 722, 'B' => 722, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722,
            'I' => 278, 'J' => 556, 'K' => 722, 'L' => 611, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667,
            'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667,
            'Y' => 667, 'Z' => 611, '[' => 333, '\\' => 278, ']' => 333, '^' => 584, '_' => 556, '`' => 333,
            'a' => 556, 'b' => 611, 'c' => 556, 'd' => 611, 'e' => 556, 'f' => 333, 'g' => 611, 'h' => 611,
            'i' => 278, 'j' => 278, 'k' => 556, 'l' => 278, 'm' => 889, 'n' => 611, 'o' => 611, 'p' => 611,
            'q' => 611, 'r' => 389, 's' => 556, 't' => 333, 'u' => 611, 'v' => 556, 'w' => 778, 'x' => 556,
            'y' => 556, 'z' => 500, '{' => 389, '|' => 280, '}' => 389, '~' => 584,
        ],
        'F3' => [ // Times-Bold
            ' ' => 250, '!' => 333, '"' => 555, '#' => 500, '$' => 500, '%' => 1000, '&' => 833, "'" => 278,
            '(' => 333, ')' => 333, '*' => 500, '+' => 570, ',' => 250, '-' => 333, '.' => 250, '/' => 278,
            '0' => 500, '1' => 500, '2' => 500, '3' => 500, '4' => 500, '5' => 500, '6' => 500, '7' => 500, '8' => 500, '9' => 500,
            ':' => 333, ';' => 333, '<' => 570, '=' => 570, '>' => 570, '?' => 500, '@' => 930,
            'A' => 722, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 778,
            'I' => 389, 'J' => 500, 'K' => 778, 'L' => 667, 'M' => 944, 'N' => 722, 'O' => 778, 'P' => 667,
            'Q' => 778, 'R' => 722, 'S' => 556, 'T' => 667, 'U' => 722, 'V' => 667, 'W' => 1000, 'X' => 722,
            'Y' => 722, 'Z' => 667, '[' => 333, '\\' => 278, ']' => 333, '^' => 444, '_' => 500, '`' => 333,
            'a' => 500, 'b' => 556, 'c' => 444, 'd' => 556, 'e' => 444, 'f' => 333, 'g' => 500, 'h' => 556,
            'i' => 278, 'j' => 333, 'k' => 556, 'l' => 278, 'm' => 833, 'n' => 556, 'o' => 500, 'p' => 556,
            'q' => 556, 'r' => 444, 's' => 389, 't' => 333, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500,
            'y' => 500, 'z' => 444, '{' => 394, '|' => 220, '}' => 394, '~' => 520,
        ],
        'F4' => [ // Times-Italic
            ' ' => 250, '!' => 333, '"' => 420, '#' => 500, '$' => 500, '%' => 833, '&' => 778, "'" => 214,
            '(' => 333, ')' => 333, '*' => 500, '+' => 675, ',' => 250, '-' => 333, '.' => 250, '/' => 278,
            '0' => 500, '1' => 500, '2' => 500, '3' => 500, '4' => 500, '5' => 500, '6' => 500, '7' => 500, '8' => 500, '9' => 500,
            ':' => 333, ';' => 333, '<' => 675, '=' => 675, '>' => 675, '?' => 500, '@' => 920,
            'A' => 611, 'B' => 611, 'C' => 667, 'D' => 722, 'E' => 611, 'F' => 611, 'G' => 722, 'H' => 722,
            'I' => 333, 'J' => 444, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 667, 'O' => 722, 'P' => 611,
            'Q' => 722, 'R' => 611, 'S' => 500, 'T' => 556, 'U' => 722, 'V' => 611, 'W' => 833, 'X' => 667,
            'Y' => 556, 'Z' => 611, '[' => 389, '\\' => 278, ']' => 389, '^' => 422, '_' => 500, '`' => 333,
            'a' => 500, 'b' => 500, 'c' => 444, 'd' => 500, 'e' => 444, 'f' => 278, 'g' => 500, 'h' => 500,
            'i' => 278, 'j' => 278, 'k' => 444, 'l' => 278, 'm' => 722, 'n' => 500, 'o' => 500, 'p' => 500,
            'q' => 444, 'r' => 389, 's' => 389, 't' => 278, 'u' => 500, 'v' => 444, 'w' => 667, 'x' => 444,
            'y' => 444, 'z' => 389, '{' => 400, '|' => 275, '}' => 400, '~' => 541,
        ],
    ];

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
        $logoPath = public_path('images/workshops/logo.jpeg');
        if (! file_exists($logoPath)) {
            $logoPath = base_path('public/images/workshops/logo.jpeg');
        }

        $hasLogo = file_exists($logoPath);
        $logoBytes = '';
        $logoWidth = 0;
        $logoHeight = 0;
        if ($hasLogo) {
            $logoBytes = (string) file_get_contents($logoPath);
            $size = @getimagesize($logoPath);
            if (is_array($size) && ($size[0] ?? 0) > 0 && ($size[1] ?? 0) > 0) {
                $logoWidth = (int) $size[0];
                $logoHeight = (int) $size[1];
            } else {
                $hasLogo = false;
            }
        }

        $lines = [
            // 1. Fondo blanco puro (#FFFFFF) para integrarse sin cortes con el fondo blanco del logo
            'q',
            '1.00 1.00 1.00 rg',
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

            // 5. Franja superior institucional decorativa centrada (ancho total: 140, centro X=421)
            'q',
            '0.75 0.07 0.24 rg',
            '351 530 70 4 re f',
            '0.96 0.62 0.04 rg',
            '421 530 70 4 re f',
            'Q',
        ];

        // 6. Logo institucional centrado en la página (ancho 56, centro X=421)
        if ($hasLogo) {
            $lines[] = 'q';
            $lines[] = '56 0 0 56 393 466 cm';
            $lines[] = '/ImLogo Do';
            $lines[] = 'Q';
        }

        $ongNameY = $hasLogo ? 448 : 488;
        $titleY = $hasLogo ? 404 : 432;
        $dividerY = $hasLogo ? 388 : 414;
        $dividerDiamondY = $hasLogo ? 384 : 410;
        $grantY = $hasLogo ? 356 : 380;
        $participantY = $hasLogo ? 312 : 332;
        $lineParticipantY = $hasLogo ? 298 : 316;
        $courseLabelY = $hasLogo ? 264 : 282;
        $courseTitleY = $hasLogo ? 228 : 242;
        $mottoY = $hasLogo ? 176 : 188;
        $sigLineY = $hasLogo ? 108 : 112;
        $sigText1Y = $hasLogo ? 92 : 96;
        $sigText2Y = $hasLogo ? 78 : 82;
        $verifDateY = $hasLogo ? 98 : 102;
        $verifCodeY = $hasLogo ? 82 : 86;

        // Ancho exacto del nombre del participante para centrar y dimensionar armónicamente su línea dorada
        $pWidth = $this->calculateTextWidth($this->toPdfEncoding($participantName), 26, 'F3');
        $lineWidth = max(260.0, min(560.0, $pWidth + 60.0));
        $lineX1 = (int) round((842 - $lineWidth) / 2);
        $lineX2 = (int) round($lineX1 + $lineWidth);

        // Alineación simétrica del pie: bloque de firma centrado a la izquierda y bloque de verificación a la derecha
        $sigCenter = 180;
        $sigLabelWidth = $this->calculateTextWidth('Dirección Académica', 11, 'F2');
        $sigOrgWidth = $this->calculateTextWidth($this->toPdfEncoding($ongName), 9, 'F1');
        $sigText1X = (int) round($sigCenter - ($sigLabelWidth / 2));
        $sigText2X = (int) round($sigCenter - ($sigOrgWidth / 2));

        $dateStr = 'Fecha de emisión: '.$issuedDate;
        $codeStr = 'Código: '.$code;
        $dateWidth = $this->calculateTextWidth($this->toPdfEncoding($dateStr), 10, 'F2');
        $codeWidth = $this->calculateTextWidth($this->toPdfEncoding($codeStr), 10, 'F2');
        $verifRightMargin = 752;
        $verifDateX = (int) round($verifRightMargin - $dateWidth);
        $verifCodeX = (int) round($verifRightMargin - $codeWidth);

        $lines = array_merge($lines, [
            // Cabecera con el nombre de la ONG (Centrado exacto)
            $this->centeredText(
                $this->toPdfEncoding(mb_strtoupper($ongName, 'UTF-8')),
                $ongNameY,
                $hasLogo ? 15 : 16,
                'F2',
                '0.75 0.07 0.24',
            ),

            // Título Principal del Certificado (Centrado exacto con métricas AFM)
            $this->centeredText(
                'CERTIFICADO DE RECONOCIMIENTO',
                $titleY,
                $hasLogo ? 26 : 28,
                'F3',
                '0.06 0.09 0.16',
            ),

            // Separador central dorado con diamante centrado
            'q',
            "0.96 0.62 0.04 RG 1.6 w 280 {$dividerY} m 562 {$dividerY} l S",
            "0.75 0.07 0.24 rg 417 {$dividerDiamondY} 8 8 re f",
            'Q',

            // Texto de otorgamiento (Centrado exacto)
            $this->centeredText(
                $this->toPdfEncoding("La {$ongName} otorga el presente certificado a:"),
                $grantY,
                13,
                'F1',
                '0.28 0.33 0.41',
            ),

            // Nombre del participante (Centrado exacto)
            $this->centeredText(
                $this->toPdfEncoding($participantName),
                $participantY,
                26,
                'F3',
                '0.75 0.07 0.24',
            ),

            // Línea dorada centrada bajo el nombre del participante
            'q',
            "0.85 0.47 0.02 RG 1.4 w {$lineX1} {$lineParticipantY} m {$lineX2} {$lineParticipantY} l S",
            'Q',

            // Texto de culminación de curso (Centrado exacto)
            $this->centeredText(
                'Por haber culminado satisfactoriamente el curso:',
                $courseLabelY,
                13,
                'F1',
                '0.20 0.25 0.33',
            ),

            // Nombre del curso (Centrado exacto)
            $this->centeredText(
                $this->toPdfEncoding($courseName),
                $courseTitleY,
                mb_strlen($courseName) > 45 ? 18 : 21,
                'F2',
                '0.06 0.09 0.16',
            ),

            // Lema de la ONG (Centrado exacto)
            $this->centeredText(
                $this->toPdfEncoding('"Educando con amor y construyendo futuro en el Perú"'),
                $mottoY,
                12,
                'F4',
                '0.39 0.45 0.55',
            ),

            // Pie de certificado: Firma izquierda centrada bajo línea de firma
            "q 0.20 0.25 0.33 RG 1.2 w 90 {$sigLineY} m 270 {$sigLineY} l S Q",
            $this->positionedText(
                $this->toPdfEncoding('Dirección Académica'),
                $sigText1X,
                $sigText1Y,
                11,
                'F2',
                '0.06 0.09 0.16',
            ),
            $this->positionedText(
                $this->toPdfEncoding($ongName),
                $sigText2X,
                $sigText2Y,
                9,
                'F1',
                '0.39 0.45 0.55',
            ),

            // Bloque derecho: fecha y código de verificación alineados armónicamente
            $this->positionedText(
                $this->toPdfEncoding($dateStr),
                $verifDateX,
                $verifDateY,
                10,
                'F2',
                '0.20 0.25 0.33',
            ),
            $this->positionedText(
                $this->toPdfEncoding($codeStr),
                $verifCodeX,
                $verifCodeY,
                10,
                'F2',
                '0.75 0.07 0.24',
            ),
        ]);

        $contentStream = implode("\n", $lines);
        $streamLength = strlen($contentStream);

        $xObjectRes = $hasLogo ? '/XObject << /ImLogo 9 0 R >> ' : '';

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >> {$xObjectRes}>> /Contents 8 0 R >>",
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>',
            7 => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>',
            8 => "<< /Length {$streamLength} >>\nstream\n{$contentStream}\nendstream",
        ];

        if ($hasLogo) {
            $logoLength = strlen($logoBytes);
            $objects[9] = "<< /Type /XObject /Subtype /Image /Width {$logoWidth} /Height {$logoHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$logoLength} >>\nstream\n{$logoBytes}\nendstream";
        }

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

    /**
     * Calcula con precisión milimétrica el ancho de un texto en puntos tipográficos PDF
     * según la fuente Adobe Type 1 seleccionada.
     */
    private function calculateTextWidth(string $text, int $fontSize, string $fontKey): float
    {
        $table = self::$fontWidths[$fontKey] ?? self::$fontWidths['F1'];
        $totalUnits = 0;
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $c = $text[$i];
            $ord = ord($c);

            // Caracteres latinos acentuados comunes en Windows-1252 / ISO-8859-1
            if ($ord >= 192 && $ord <= 221) {
                $totalUnits += $table['A'] ?? 700;
            } elseif ($ord >= 224 && $ord <= 254) {
                $totalUnits += $table['a'] ?? 520;
            } elseif (isset($table[$c])) {
                $totalUnits += $table[$c];
            } else {
                $totalUnits += 550;
            }
        }

        return ($totalUnits / 1000) * $fontSize;
    }

    private function centeredText(string $text, int $y, int $fontSize, string $fontKey, string $rgb): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $measuredWidth = $this->calculateTextWidth($text, $fontSize, $fontKey);
        $x = max(45, (int) round((842 - $measuredWidth) / 2));

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
