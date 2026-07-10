<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'reference_paiement')) {
                $table->string('reference_paiement', 100)->nullable()->after('montant_paye');
            }

            if (! Schema::hasColumn('reservations', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 255)->nullable()->after('reference_paiement');
            }

            if (! Schema::hasColumn('reservations', 'refund_status')) {
                $table->string('refund_status', 30)->nullable()->after('stripe_payment_intent_id');
            }

            if (! Schema::hasColumn('reservations', 'refund_reference')) {
                $table->string('refund_reference', 255)->nullable()->after('refund_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'refund_reference')) {
                $table->dropColumn('refund_reference');
            }

            if (Schema::hasColumn('reservations', 'refund_status')) {
                $table->dropColumn('refund_status');
            }

            if (Schema::hasColumn('reservations', 'stripe_payment_intent_id')) {
                $table->dropColumn('stripe_payment_intent_id');
            }

            if (Schema::hasColumn('reservations', 'reference_paiement')) {
                $table->dropColumn('reference_paiement');
            }
        });
    }
};
