<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_bon_entrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_entree_id')->constrained('bon_entrees')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->integer('quantite_entree_lig_bon_ent');
            $table->decimal('prix_unitaire_dachat_lig_bon_ent', 10, 2);
            $table->decimal('sous_total', 10, 2);
            $table->timestamps();

            $table->index(['produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_bon_entrees');
    }
};
