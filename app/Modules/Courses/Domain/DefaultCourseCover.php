<?php

namespace App\Modules\Courses\Domain;

/** Portada generada por el sistema para cursos sin imagen propia. */
final class DefaultCourseCover
{
    /** @var list<array{string, string}> */
    private const PALETTES = [
        ['#1d4ed8', '#7c3aed'],
        ['#0f766e', '#2563eb'],
        ['#b45309', '#be123c'],
        ['#15803d', '#0e7490'],
        ['#6d28d9', '#be185d'],
        ['#334155', '#0369a1'],
    ];

    public static function svg(int $courseId, string $name): string
    {
        [$from, $to] = self::PALETTES[$courseId % count(self::PALETTES)];
        $initial = htmlspecialchars(mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?', ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" role="img" aria-hidden="true">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient></defs>
<rect width="640" height="360" fill="url(#g)"/>
<circle cx="560" cy="40" r="120" fill="#ffffff" fill-opacity="0.08"/>
<circle cx="60" cy="340" r="90" fill="#ffffff" fill-opacity="0.08"/>
<text x="320" y="230" text-anchor="middle" font-family="system-ui, sans-serif" font-size="160" font-weight="700" fill="#ffffff">{$initial}</text>
</svg>
SVG;
    }
}
