<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('type_abonnement_adherent')) {
            return;
        }

        Schema::create('type_abonnement_adherent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->integer('nb_mois');
            $table->float('tarif');
            $table->float('prix_unitaire');
            $table->enum('niveau_sportif_cible', ['debutant', 'intermediaire', 'expert', 'tous'])->default('tous');
            $table->string('sport_cible')->nullable();
            $table->json('avantages')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_abonnement_adherent');
    }
};
