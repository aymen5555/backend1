<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify commandes table
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('statut_paiement', ['non_paye', 'paye', 'rembourse'])->change();
        });

        // Modify reservation_activites table
        Schema::table('reservation_activites', function (Blueprint $table) {
            $table->enum('statut_paiement', ['non_paye', 'paye', 'rembourse'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('statut_paiement', ['non_paye', 'paye'])->change();
        });

        Schema::table('reservation_activites', function (Blueprint $table) {
            $table->enum('statut_paiement', ['non_paye', 'paye'])->change();
        });
    }
};
