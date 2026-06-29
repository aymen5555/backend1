<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservation_activites', function (Blueprint $table) {
            $table->decimal('montant_paye', 8, 2)->nullable()->after('notes');
            $table->string('reference_paiement', 100)->nullable()->after('montant_paye');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_activites', function (Blueprint $table) {
            $table->dropColumn(['montant_paye', 'reference_paiement']);
        });
    }
};
