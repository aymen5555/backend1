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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('complexe_id');
            $table->unsignedBigInteger('type_abonnement_id');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->double('montant_vente');
            $table->integer('remise')->default(0);
            $table->double('montant_apres_remise');
            $table->enum('statut', ['actif', 'expire', 'annule'])->default('actif');
            $table->boolean('paye')->default(false);
            $table->double('reste_a_payer')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('complexe_id')->references('id')->on('complexes')->onDelete('cascade');
            $table->foreign('type_abonnement_id')->references('id')->on('type_abonnement_adherent')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements_adherent');
    }
};
