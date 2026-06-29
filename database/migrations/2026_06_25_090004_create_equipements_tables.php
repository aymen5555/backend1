<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue global d'équipements
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->string('nom_eq');
            $table->string('icone_eq')->nullable(); // emoji ou code icône
            $table->timestamps();
        });

        // Table pivot many-to-many complexe <-> equipement
        Schema::create('complexe_equipement', function (Blueprint $table) {
            $table->unsignedBigInteger('complexe_id');
            $table->unsignedBigInteger('equipement_id');
            $table->primary(['complexe_id', 'equipement_id']);

            $table->foreign('complexe_id')->references('id')->on('complexes')->onDelete('cascade');
            $table->foreign('equipement_id')->references('id')->on('equipements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complexe_equipement');
        Schema::dropIfExists('equipements');
    }
};
