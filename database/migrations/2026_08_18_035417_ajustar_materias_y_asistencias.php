<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Se usa SQL nativo (en vez de ->change()) porque ->change()
        // requiere el paquete doctrine/dbal, que este proyecto no tiene
        // instalado.
        DB::statement('ALTER TABLE materias MODIFY curso_id BIGINT UNSIGNED NULL');

        if (! Schema::hasColumn('asistencias', 'observacion')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->string('observacion', 255)->nullable()->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('asistencias', 'observacion')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->dropColumn('observacion');
            });
        }

        DB::statement('ALTER TABLE materias MODIFY curso_id BIGINT UNSIGNED NOT NULL');
    }
};