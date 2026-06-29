<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categorie_produits')->cascadeOnDelete();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->float('prix');
            $table->float('prix_achat')->nullable();
            $table->enum('sport_cible', [
                'football', 'padel', 'tennis', 'natation', 'musculation',
                'yoga', 'fitness', 'basketball', 'volleyball', 'handball', 'general',
            ]);
            $table->enum('niveau_cible', ['debutant', 'intermediaire', 'expert', 'tous']);
            $table->string('image')->nullable();
            $table->string('reference')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
