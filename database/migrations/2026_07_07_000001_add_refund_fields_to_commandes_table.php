<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('modalite_paiement');
            $table->enum('refund_status', ['not_requested', 'pending', 'succeeded', 'failed'])->default('not_requested')->after('stripe_payment_intent_id');
            $table->string('refund_reference')->nullable()->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'refund_status', 'refund_reference']);
        });
    }
};
