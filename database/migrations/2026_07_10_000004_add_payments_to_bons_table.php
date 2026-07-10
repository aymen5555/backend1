<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_entrees', function (Blueprint $table) {
            $table->decimal('montant_paye', 12, 2)->default(0)->after('total_ttc_bon_ent');
            $table->string('reference_paiement')->nullable()->after('montant_paye');
            $table->string('statut_paiement')->default('non_paye')->after('reference_paiement');
        });

        Schema::table('bon_sorties', function (Blueprint $table) {
            $table->decimal('montant_paye', 12, 2)->default(0)->after('total_ttc_bon_sor');
            $table->string('reference_paiement')->nullable()->after('montant_paye');
            $table->string('statut_paiement')->default('non_paye')->after('reference_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('bon_entrees', function (Blueprint $table) {
            $table->dropColumn(['montant_paye', 'reference_paiement', 'statut_paiement']);
        });

        Schema::table('bon_sorties', function (Blueprint $table) {
            $table->dropColumn(['montant_paye', 'reference_paiement', 'statut_paiement']);
        });
    }
};
