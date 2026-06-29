<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bon_entrees', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->date('date_bon_ent');
            $table->decimal('total_ttc_bon_ent', 10, 2)->default(0);
            $table->foreignId('fournisseur_interne_id')->constrained('fournisseurs_internes')->restrictOnDelete();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['complexe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bon_entrees');
    }
};
