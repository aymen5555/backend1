<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vente_directes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits');
            $table->foreignId('complexe_id')->constrained('complexes');
            $table->integer('quantite');
            $table->float('prix_unitaire');
            $table->float('montant_total');
            $table->enum('modalite_paiement', ['especes', 'carte']);
            $table->string('client_nom')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vente_directes');
    }
};
