<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('materia_id')
                ->constrained('materias')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('semestre');
            $table->string('tipo', 10);
            $table->unsignedTinyInteger('orden');
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('puntos_max');
            $table->timestamps();

            // Un lugar por parcial (1 a 5) y un solo examen por materia y semestre.
            $table->unique(['materia_id', 'semestre', 'tipo', 'orden'], 'evaluaciones_unica');
        });

        // Restricción en el servidor: aunque falle la validación, la base rechaza
        // semestres inválidos, puntajes máximos fuera de 5 a 100 y más de 5 parciales.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE evaluaciones ADD CONSTRAINT evaluaciones_reglas_chk CHECK ("
                . "semestre IN (1, 2) AND puntos_max BETWEEN 5 AND 100 AND "
                . "((tipo = 'parcial' AND orden BETWEEN 1 AND 5) OR (tipo = 'examen' AND orden = 1)))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
