<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('courses')->select('id', 'description', 'objectives')->orderBy('id')->chunkById(
            100,
            function ($courses): void {
                foreach ($courses as $course) {
                    DB::table('courses')->where('id', $course->id)->update([
                        'description' => $this->toParagraphDocument($course->description),
                        'objectives' => $this->toParagraphDocument($course->objectives),
                    ]);
                }
            },
        );

        DB::table('lessons')->select('id', 'content_type', 'content')->orderBy('id')->chunkById(
            100,
            function ($lessons): void {
                foreach ($lessons as $lesson) {
                    $content = $lesson->content_type === 'text'
                        ? $this->toParagraphDocument($lesson->content)
                        : $this->toJsonString($lesson->content);

                    DB::table('lessons')->where('id', $lesson->id)->update(['content' => $content]);
                }
            },
        );

        DB::table('questions')->select('id', 'statement')->orderBy('id')->chunkById(
            100,
            function ($questions): void {
                foreach ($questions as $question) {
                    DB::table('questions')->where('id', $question->id)->update([
                        'statement' => $this->toParagraphDocument($question->statement),
                    ]);
                }
            },
        );

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE courses ALTER COLUMN description TYPE jsonb USING description::jsonb');
            DB::statement('ALTER TABLE courses ALTER COLUMN objectives TYPE jsonb USING objectives::jsonb');
            DB::statement('ALTER TABLE lessons ALTER COLUMN content TYPE jsonb USING content::jsonb');
            DB::statement('ALTER TABLE questions ALTER COLUMN statement TYPE jsonb USING statement::jsonb');
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->string('intro_video_provider')->nullable();
            $table->string('intro_video_id')->nullable();
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->jsonb('instructions')->nullable();
        });

        Schema::create('course_media_images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('storage_path')->unique();
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['course_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_media_images');

        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropColumn('instructions');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn(['intro_video_provider', 'intro_video_id']);
        });

        $isPostgres = DB::connection()->getDriverName() === 'pgsql';
        DB::table('courses')->select('id', 'description', 'objectives')->orderBy('id')->chunkById(
            100,
            function ($courses) use ($isPostgres): void {
                foreach ($courses as $course) {
                    DB::table('courses')->where('id', $course->id)->update([
                        'description' => $this->toLegacyColumnValue($course->description, $isPostgres),
                        'objectives' => $this->toLegacyColumnValue($course->objectives, $isPostgres),
                    ]);
                }
            },
        );

        DB::table('lessons')->select('id', 'content')->orderBy('id')->chunkById(
            100,
            function ($lessons) use ($isPostgres): void {
                foreach ($lessons as $lesson) {
                    DB::table('lessons')->where('id', $lesson->id)->update([
                        'content' => $this->toLegacyColumnValue($lesson->content, $isPostgres),
                    ]);
                }
            },
        );

        DB::table('questions')->select('id', 'statement')->orderBy('id')->chunkById(
            100,
            function ($questions) use ($isPostgres): void {
                foreach ($questions as $question) {
                    DB::table('questions')->where('id', $question->id)->update([
                        'statement' => $this->toLegacyColumnValue($question->statement, $isPostgres),
                    ]);
                }
            },
        );

        if ($isPostgres) {
            DB::statement("ALTER TABLE courses ALTER COLUMN description TYPE text USING description::jsonb #>> '{}'");
            DB::statement("ALTER TABLE courses ALTER COLUMN objectives TYPE text USING objectives::jsonb #>> '{}'");
            DB::statement("ALTER TABLE lessons ALTER COLUMN content TYPE text USING content::jsonb #>> '{}'");
            DB::statement("ALTER TABLE questions ALTER COLUMN statement TYPE text USING statement::jsonb #>> '{}'");
        }
    }

    private function toLegacyColumnValue(?string $value, bool $isPostgres): ?string
    {
        if ($value === null) {
            return null;
        }

        $plainText = $this->plainText(json_decode($value, true, flags: JSON_THROW_ON_ERROR));

        return $isPostgres ? json_encode($plainText, JSON_THROW_ON_ERROR) : $plainText;
    }

    private function plainText(mixed $node): string
    {
        if (! is_array($node)) {
            return is_string($node) ? $node : '';
        }

        if (($node['type'] ?? null) === 'text') {
            return is_string($node['text'] ?? null) ? $node['text'] : '';
        }

        if (($node['type'] ?? null) === 'image') {
            return is_string($node['attrs']['alt'] ?? null) ? $node['attrs']['alt'] : '';
        }

        $separator = in_array($node['type'] ?? null, [
            'doc',
            'bulletList',
            'orderedList',
            'blockquote',
            'listItem',
        ], true) ? "\n" : '';
        $children = [];

        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            $children[] = $this->plainText($child);
        }

        return implode($separator, $children);
    }

    private function toParagraphDocument(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => $text === '' ? [] : [['type' => 'text', 'text' => $text]],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function toJsonString(?string $text): ?string
    {
        return $text === null ? null : json_encode($text, JSON_THROW_ON_ERROR);
    }
};
