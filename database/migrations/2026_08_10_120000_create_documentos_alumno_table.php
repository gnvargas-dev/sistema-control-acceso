<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documentos_alumno', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alumno_id')
                ->constrained('alumnos')
                ->cascadeOnDelete();

            $table->string('categoria', 60);
            $table->string('documento', 60);
            $table->date('fecha_entrega')->nullable();
            $table->timestamps();

            $table->unique(['alumno_id', 'documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_alumno');
    }
};