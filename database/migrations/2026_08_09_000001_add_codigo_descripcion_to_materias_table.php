<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->string('codigo', 30)->nullable()->after('nombre');
            $table->string('descripcion', 255)->nullable()->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'descripcion']);
        });
    }
};
