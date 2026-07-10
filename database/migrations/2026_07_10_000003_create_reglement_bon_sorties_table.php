<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglement_bon_sorties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_sortie_id')->constrained('bon_sorties')->onDelete('cascade');
            $table->string('type')->default('paiement');
            $table->decimal('montant', 12, 2)->default(0);
            $table->string('reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_bon_sorties');
    }
};
