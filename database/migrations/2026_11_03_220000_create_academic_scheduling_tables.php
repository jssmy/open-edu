<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->restrictOnDelete();
            $table->string('modality', 20)->default('in_person');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            $table->timestamps();

            $table->index(['teacher_id', 'academic_period_id']);
            $table->index(['classroom_id', 'academic_period_id']);
            $table->index(['course_id']);
        });

        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_schedule_id')->constrained('course_schedules')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->string('starts_at', 5);
            $table->string('ends_at', 5);
            $table->timestamps();
        });

        // Migrar asignaciones existentes de course_teacher a programaciones pendientes (FR-010)
        $now = now();
        $rows = DB::table('course_teacher')->select(['course_id', 'user_id'])->get();
        foreach ($rows as $row) {
            DB::table('course_schedules')->insert([
                'course_id' => $row->course_id,
                'teacher_id' => $row->user_id,
                'academic_period_id' => null,
                'modality' => 'virtual',
                'classroom_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
        Schema::dropIfExists('course_schedules');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('academic_periods');
    }
};
