<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Identity\Domain\Role;
use Illuminate\Support\Facades\DB;

it('stores course text as a validated rich document and normalizes its introduction video', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $description = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Contenido destacado', 'marks' => [['type' => 'bold']]],
                ],
            ],
        ],
    ];

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => $description,
        'objectives' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
        'intro_video_url' => 'https://youtu.be/abcdefghijk',
    ])->assertRedirect();

    $course = Course::query()->sole();

    expect($course->description)->toEqual($description)
        ->and($course->intro_video_provider)->toBe('youtube')
        ->and($course->intro_video_id)->toBe('abcdefghijk')
        ->and($course->introVideoUrl())->toBe('https://www.youtube.com/watch?v=abcdefghijk');
});

it('rejects rich documents containing unsupported nodes', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => [
            'type' => 'doc',
            'content' => [['type' => 'script', 'attrs' => ['src' => 'https://evil.example/x.js']]],
        ],
    ])->assertSessionHasErrors('description');

    $this->assertDatabaseCount('courses', 0);
});

it('rejects unsafe link protocols in rich documents', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Abrir',
                    'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]],
                ]],
            ]],
        ],
    ])->assertSessionHasErrors('description');
});

it('rejects external image sources that are not HTTPS', function (string $source) {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'image',
                'attrs' => ['src' => $source, 'alt' => 'Diagrama'],
            ]],
        ],
    ])->assertSessionHasErrors('description');

    $this->assertDatabaseCount('courses', 0);
})->with([
    'HTTP image' => 'http://images.example.test/course.png',
    'data URI image' => 'data:image/png;base64,AAAA',
]);

it('requires meaningful image alternatives and accepts explicitly decorative images', function (
    array $attributes,
    bool $valid,
) {
    $manager = userWithRole(Role::GestorDeCursos);
    $document = [
        'type' => 'doc',
        'content' => [[
            'type' => 'image',
            'attrs' => [
                'src' => 'https://images.example.test/course.webp',
                ...$attributes,
            ],
        ]],
    ];

    $response = $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'description' => $document,
    ]);

    if ($valid) {
        $response->assertRedirect();
        $this->assertDatabaseCount('courses', 1);
    } else {
        $response->assertSessionHasErrors('description');
        $this->assertDatabaseCount('courses', 0);
    }
})->with([
    'missing alternative' => [[], false],
    'descriptive alternative' => [['alt' => 'Diagrama celular'], true],
    'decorative alternative' => [['alt' => ''], true],
]);

it('rejects video URLs outside the exact YouTube and Vimeo hosts', function (string $url) {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)->post('/courses', [
        'name' => 'Biología',
        'intro_video_url' => $url,
    ])->assertSessionHasErrors('intro_video_url');

    $this->assertDatabaseCount('courses', 0);
})->with([
    'lookalike host' => 'https://youtube.com.attacker.test/watch?v=abcdefghijk',
    'non-HTTPS URL' => 'http://www.youtube.com/watch?v=abcdefghijk',
    'invalid YouTube ID' => 'https://www.youtube.com/watch?v=short',
    'unsupported provider' => 'https://videos.example.test/abcdefghijk',
]);

it('restores plain text when rolling back and reapplying the rich-content migration', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The rich-content migration uses PostgreSQL JSONB conversions.');
    }

    $course = Course::factory()->create([
        'description' => richTextDocument('Descripción original'),
        'objectives' => richTextDocument('Objetivos originales'),
    ]);
    $unit = Unit::query()->create([
        'course_id' => $course->id,
        'title' => 'Unidad de migración',
        'position' => 1,
    ]);
    $lesson = Lesson::query()->create([
        'unit_id' => $unit->id,
        'title' => 'Lección de migración',
        'position' => 1,
        'content_type' => 'text',
        'content' => richTextDocument('Contenido original'),
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación de migración',
        'instructions' => richTextDocument('Instrucciones originales'),
        'max_attempts' => 1,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => false,
    ]);
    $question = Question::query()->create([
        'assessment_id' => $assessment->id,
        'statement' => richTextDocument('Enunciado original'),
        'points' => 1,
        'position' => 1,
    ]);
    $migration = require database_path(
        'migrations/2026_11_03_183631_migrate_course_rich_text_and_create_course_media_images.php',
    );

    $migration->down();

    expect(DB::table('courses')->where('id', $course->id)->value('description'))
        ->toBe('Descripción original')
        ->and(DB::table('lessons')->where('id', $lesson->id)->value('content'))
        ->toBe('Contenido original')
        ->and(DB::table('questions')->where('id', $question->id)->value('statement'))
        ->toBe('Enunciado original');

    $migration->up();

    expect(Course::query()->findOrFail($course->id)->description)
        ->toEqual(richTextDocument('Descripción original'))
        ->and(Lesson::query()->findOrFail($lesson->id)->content)
        ->toEqual(richTextDocument('Contenido original'))
        ->and(Question::query()->findOrFail($question->id)->statement)
        ->toEqual(richTextDocument('Enunciado original'));
});
