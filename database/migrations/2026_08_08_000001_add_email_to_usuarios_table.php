<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('usuarios', 'email')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('email')->nullable()->unique()->after('cedula');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'email')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            });
        }
    }
};