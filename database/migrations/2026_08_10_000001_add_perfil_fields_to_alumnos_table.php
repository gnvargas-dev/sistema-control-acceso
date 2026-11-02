<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->after('curso_id');
            $table->string('telefono', 30)->nullable()->after('fecha_nacimiento');
            $table->string('foto')->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropColumn(['fecha_nacimiento', 'telefono', 'foto']);
        });
    }
};