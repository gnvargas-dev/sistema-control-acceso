<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alumno_id')
                ->constrained('alumnos')
                ->cascadeOnDelete();

            $table->foreignId('materia_id')
                ->constrained('materias')
                ->restrictOnDelete();

            $table->decimal('valor', 3, 2);
            $table->string('periodo', 50);

            $table->foreignId('cargado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique([
                'alumno_id',
                'materia_id',
                'periodo',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
