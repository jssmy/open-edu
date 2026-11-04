<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('schedule_blocks');
        Schema::dropIfExists('course_schedules');
        Schema::dropIfExists('academic_periods');

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('group_code', 80);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_students')->default(30);
            $table->string('modality', 20)->default('in_person');
            $table->string('status', 20)->default('published');
            $table->timestamps();

            $table->unique(['course_id', 'group_code']);
            $table->index(['course_id', 'status']);
        });

        Schema::create('course_offering_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_offering_id', 'teacher_id']);
            $table->index(['teacher_id']);
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->cascadeOnDelete();
            $table->string('title', 160);
            $table->date('session_date');
            $table->string('starts_at', 5);
            $table->string('ends_at', 5);
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            $table->string('meeting_url', 500)->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();

            $table->index(['course_offering_id', 'session_date']);
            $table->index(['teacher_id', 'session_date']);
            $table->index(['classroom_id', 'session_date']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('course_offering_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_offerings')
                ->restrictOnDelete();
            $table->index(['course_offering_id', 'status']);
        });

        // Migrar asignaciones existentes de course_teacher a programaciones pendientes (en borrador sin fechas)
        $now = now();
        $rows = DB::table('course_teacher')->select(['course_id', 'user_id'])->get();
        foreach ($rows as $row) {
            $offeringId = DB::table('course_offerings')->insertGetId([
                'course_id' => $row->course_id,
                'group_code' => 'PEND-'.$row->user_id,
                'starts_on' => null,
                'ends_on' => null,
                'max_students' => 30,
                'modality' => 'virtual',
                'status' => 'draft',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('course_offering_teacher')->insert([
                'course_offering_id' => $offeringId,
                'teacher_id' => $row->user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['course_offering_id', 'status']);
            $table->dropConstrainedForeignId('course_offering_id');
        });

        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('course_offering_teacher');
        Schema::dropIfExists('course_offerings');
    }
};
