<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alumno_id')
                ->constrained('alumnos')
                ->cascadeOnDelete();

            $table->foreignId('materia_id')
                ->constrained('materias')
                ->restrictOnDelete();

            $table->date('fecha');

            $table->enum('estado', [
                'presente',
                'ausente',
                'tarde',
                'justificado',
            ]);

            $table->foreignId('cargado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'alumno_id',
                'materia_id',
                'fecha',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
