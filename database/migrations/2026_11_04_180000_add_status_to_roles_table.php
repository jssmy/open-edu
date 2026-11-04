<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('description', 255)->nullable()->after('guard_name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        DB::table('roles')
            ->whereIn('name', ['administrador', 'gestor_de_cursos', 'docente', 'estudiante'])
            ->update(['is_system' => true]);

        DB::statement('CREATE UNIQUE INDEX roles_name_lower_unique ON roles (lower(name))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS roles_name_lower_unique');

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_active', 'is_system']);
        });
    }
};
