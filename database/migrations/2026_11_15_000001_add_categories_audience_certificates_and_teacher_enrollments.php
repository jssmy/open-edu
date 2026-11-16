<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->string('role_name', 64)->default('estudiante')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $defaultCategories = [
            'Onboarding' => 'docente',
            'Capacitación' => 'docente',
            'Disciplina' => 'estudiante',
            'Expresión' => 'estudiante',
            'Creatividad' => 'estudiante',
            'Lógica' => 'estudiante',
            'Idiomas' => 'estudiante',
            'Digital' => 'estudiante',
        ];

        foreach ($defaultCategories as $name => $roleName) {
            DB::table('course_categories')->insert([
                'name' => $name,
                'slug' => Str::slug($name),
                'role_name' => $roleName,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->after('id')
                ->constrained('course_categories')
                ->restrictOnDelete();
            $table->string('target_audience', 20)
                ->default('student')
                ->after('status');
            $table->boolean('issues_certificate')
                ->default(true)
                ->after('target_audience');

            $table->index(['target_audience', 'status']);
            $table->index(['category_id']);
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->foreignId('user_id')
                ->nullable()
                ->after('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->foreignId('user_id')
                ->nullable()
                ->after('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::table('attempts', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->foreignId('user_id')
                ->nullable()
                ->after('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unique(['assessment_id', 'user_id', 'attempt_number']);
            $table->index(['assessment_id', 'user_id', 'status']);
        });

        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')
                ->unique()
                ->constrained('enrollments')
                ->cascadeOnDelete();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();
            $table->string('code', 64)->unique();
            $table->string('file_url', 500);
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');

        Schema::table('attempts', function (Blueprint $table): void {
            $table->dropUnique(['assessment_id', 'user_id', 'attempt_number']);
            $table->dropIndex(['assessment_id', 'user_id', 'status']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'lesson_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'course_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex(['target_audience', 'status']);
            $table->dropIndex(['category_id']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['target_audience', 'issues_certificate']);
        });

        Schema::dropIfExists('course_categories');
    }
};
