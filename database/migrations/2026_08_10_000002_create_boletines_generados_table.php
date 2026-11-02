<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('boletines_generados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alumno_id')
                ->constrained('alumnos')
                ->cascadeOnDelete();

            $table->foreignId('curso_id')
                ->nullable()
                ->constrained('cursos')
                ->nullOnDelete();

            $table->string('periodo', 50);
            $table->string('archivo');

            $table->foreignId('generado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->unsignedInteger('descargas')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletines_generados');
    }
};