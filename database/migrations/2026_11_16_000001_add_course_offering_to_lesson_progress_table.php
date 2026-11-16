<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->foreignId('course_offering_id')
                ->nullable()
                ->after('user_id')
                ->constrained('course_offerings')
                ->cascadeOnDelete();

            $table->unique(['course_offering_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->dropUnique(['course_offering_id', 'lesson_id']);
            $table->dropConstrainedForeignId('course_offering_id');
        });
    }
};
