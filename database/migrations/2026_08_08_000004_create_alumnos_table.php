<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->unique()
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->restrictOnDelete();

            $table->string('legajo')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
