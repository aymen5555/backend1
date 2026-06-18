<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('abonnements_adherent')) {
            return;
        }

        Schema::create('abonnements_adherent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->foreignId('type_abonnement_id')->constrained('type_abonnement_adherent')->cascadeOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->float('montant_vente');
            $table->integer('remise')->default(0);
            $table->float('montant_apres_remise');
            $table->enum('statut', ['actif', 'expire', 'annule'])->default('actif');
            $table->boolean('paye')->default(false);
            $table->float('reste_a_payer')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements_adherent');
    }
};
