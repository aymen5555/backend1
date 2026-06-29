<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->enum('statut', ['en_attente', 'confirmee', 'preparee', 'livree', 'annulee'])->default('en_attente');
            $table->enum('statut_paiement', ['non_paye', 'paye'])->default('non_paye');
            $table->enum('modalite_paiement', ['especes', 'carte'])->nullable();
            $table->float('montant_total')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
