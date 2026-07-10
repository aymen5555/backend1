<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements_adherent', function (Blueprint $table) {
            $table->enum('refund_status', ['not_requested', 'pending', 'succeeded', 'failed'])->default('not_requested')->after('reste_a_payer');
            $table->string('refund_reference')->nullable()->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('abonnements_adherent', function (Blueprint $table) {
            $table->dropColumn('refund_status');
            $table->dropColumn('refund_reference');
        });
    }
};
