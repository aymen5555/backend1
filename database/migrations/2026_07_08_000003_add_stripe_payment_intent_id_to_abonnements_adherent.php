<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements_adherent', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('refund_reference');
        });
    }

    public function down(): void
    {
        Schema::table('abonnements_adherent', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_intent_id');
        });
    }
};
