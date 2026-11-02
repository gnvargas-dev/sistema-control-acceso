<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedSmallInteger('anio');
            $table->timestamps();

            $table->unique(['nombre', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
