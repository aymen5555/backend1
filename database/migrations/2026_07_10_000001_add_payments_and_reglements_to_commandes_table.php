<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            if (! Schema::hasColumn('commandes', 'montant_paye')) {
                $table->decimal('montant_paye', 8, 2)->default(0)->after('montant_total');
            }

            if (! Schema::hasColumn('commandes', 'reference_paiement')) {
                $table->string('reference_paiement', 100)->nullable()->after('montant_paye');
            }

            // Allow partial payments and refunds
            $table->enum('statut_paiement', ['non_paye', 'partiel', 'paye', 'rembourse'])->change();
        });

        Schema::create('reglement_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('type')->default('paiement');
            $table->decimal('montant', 10, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            if (Schema::hasColumn('commandes', 'montant_paye')) {
                $table->dropColumn('montant_paye');
            }
            if (Schema::hasColumn('commandes', 'reference_paiement')) {
                $table->dropColumn('reference_paiement');
            }
            // Revert enum to original (may fail on some DB drivers if values in use)
            $table->enum('statut_paiement', ['non_paye', 'paye', 'rembourse'])->change();
        });

        Schema::dropIfExists('reglement_commandes');
    }
};
