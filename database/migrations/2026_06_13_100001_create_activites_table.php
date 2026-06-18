<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->enum('sport', [
                'yoga', 'fitness', 'natation', 'musculation',
                'football', 'padel', 'tennis', 'basketball', 'volleyball', 'handball',
            ]);
            $table->enum('niveau', ['debutant', 'intermediaire', 'expert', 'tous'])->default('tous');
            $table->unsignedSmallInteger('capacite');
            $table->decimal('prix', 8, 2);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->json('jours'); // e.g. ["lundi","mercredi","vendredi"]
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activites');
    }
};
