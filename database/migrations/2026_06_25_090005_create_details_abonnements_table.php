<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('details_abonnements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_abonnement_adherent_id');
            $table->enum('jour_seance', [
                'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
            ]);
            $table->time('heure_debut_de_abo');
            $table->time('heure_fin_de_abo');
            $table->timestamps();

            $table->foreign('type_abonnement_adherent_id')
                ->references('id')
                ->on('type_abonnement_adherent')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('details_abonnements');
    }
};
