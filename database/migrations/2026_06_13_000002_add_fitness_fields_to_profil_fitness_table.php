<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_fitness', function (Blueprint $table) {
            $table->integer('taille')->nullable()->after('user_id');           // cm
            $table->float('poids', 8, 2)->nullable()->after('taille');         // kg
            $table->float('poids_cible', 8, 2)->nullable()->after('imc');      // kg, nullable
            $table->boolean('verif_fitness')->default(false)->after('poids_cible');
        });
    }

    public function down(): void
    {
        Schema::table('profil_fitness', function (Blueprint $table) {
            $table->dropColumn(['taille', 'poids', 'poids_cible', 'verif_fitness']);
        });
    }
};
