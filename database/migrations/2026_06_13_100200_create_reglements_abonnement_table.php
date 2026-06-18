<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reglements_abonnement')) {
            return;
        }

        Schema::create('reglements_abonnement', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abonnement_id');
            $table->double('montant');
            $table->date('date_reglement');
            $table->enum('modalite', ['especes', 'carte']);
            $table->string('reference')->nullable();
            $table->boolean('encaisse')->default(false);
            $table->timestamps();

            $table->foreign('abonnement_id')->references('id')->on('abonnements_adherent')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglements_abonnement');
    }
};
