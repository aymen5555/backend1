<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_bon_sorties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_sortie_id')->constrained('bon_sorties')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->integer('quantite_entree_lig_bon_sor');
            $table->decimal('prix_unitaire_constate')->nullable();
            $table->timestamps();

            $table->index(['produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_bon_sorties');
    }
};
