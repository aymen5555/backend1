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
            $table->string('stripe_payment_intent_id', 255)->nullable()->after('reference_paiement');
            $table->string('refund_status', 30)->nullable()->after('stripe_payment_intent_id');
            $table->string('refund_reference', 255)->nullable()->after('refund_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_activites', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'refund_status', 'refund_reference']);
        });
    }
};
