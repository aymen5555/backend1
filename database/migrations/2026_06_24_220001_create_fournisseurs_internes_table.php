<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs_internes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_f_int');
            $table->string('raison_sociale_f_int')->nullable();
            $table->string('contact_f_int')->nullable();
            $table->string('tel_f_int', 30)->nullable();
            $table->string('email_f_int')->nullable();
            $table->text('adresse_f_int')->nullable();
            $table->string('matricule_fiscale_f_int', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs_internes');
    }
};
