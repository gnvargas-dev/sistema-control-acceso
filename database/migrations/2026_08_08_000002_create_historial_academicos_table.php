<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historial_academicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->string('periodo', 50);
            $table->decimal('promedio', 5, 2)->default(0);
            $table->decimal('asistencia', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['alumno_id', 'anio', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_academicos');
    }
};