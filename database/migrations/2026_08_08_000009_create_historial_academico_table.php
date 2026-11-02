<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historial_academico', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alumno_id')
                ->constrained('alumnos')
                ->cascadeOnDelete();

            $table->text('detalle');
            $table->string('periodo', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_academico');
    }
};
