<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('type', ['online', 'manual'])->default('online')->after('status');
            $table->enum('modalite_paiement', ['carte', 'especes'])->nullable()->after('type');
            $table->enum('statut_paiement', ['non_paye', 'paye', 'rembourse'])->default('non_paye')->after('modalite_paiement');
            $table->decimal('montant_paye', 8, 2)->default(0)->after('statut_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['type', 'modalite_paiement', 'statut_paiement', 'montant_paye']);
        });
    }
};
