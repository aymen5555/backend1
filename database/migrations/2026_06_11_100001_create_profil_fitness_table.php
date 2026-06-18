<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_fitness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('objectif_sportif');          // e.g. "perdre du poids", "compétition", "loisir"
            $table->string('niveau_sportif');             // "débutant", "intermédiaire", "avancé"
            $table->decimal('budget_mensuel_min', 8, 2)->default(0);
            $table->decimal('budget_mensuel_max', 8, 2)->default(200);
            $table->string('sport_prefere');              // "padel", "tennis", "football"
            $table->decimal('imc', 5, 2)->nullable();     // Body Mass Index, optional
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_fitness');
    }
};
