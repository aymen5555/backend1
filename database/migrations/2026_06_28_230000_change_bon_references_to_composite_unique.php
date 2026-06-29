<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_entrees', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->unique(['complexe_id', 'reference']);
        });

        Schema::table('bon_sorties', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->unique(['complexe_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('bon_entrees', function (Blueprint $table) {
            $table->dropUnique(['complexe_id', 'reference']);
            $table->unique('reference');
        });

        Schema::table('bon_sorties', function (Blueprint $table) {
            $table->dropUnique(['complexe_id', 'reference']);
            $table->unique('reference');
        });
    }
};
