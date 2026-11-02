<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->restrictOnDelete();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['nombre', 'curso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
