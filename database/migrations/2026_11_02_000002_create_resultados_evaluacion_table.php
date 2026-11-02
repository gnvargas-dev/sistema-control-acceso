<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resultados_evaluacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluacion_id')
                ->constrained('evaluaciones')
                ->cascadeOnDelete();

            $table->foreignId('alumno_id')
                ->constrained('alumnos')
                ->cascadeOnDelete();

            $table->decimal('puntos', 5, 2);

            $table->foreignId('cargado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['evaluacion_id', 'alumno_id'], 'resultados_evaluacion_unico');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE resultados_evaluacion ADD CONSTRAINT resultados_evaluacion_puntos_chk CHECK (puntos >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_evaluacion');
    }
};
