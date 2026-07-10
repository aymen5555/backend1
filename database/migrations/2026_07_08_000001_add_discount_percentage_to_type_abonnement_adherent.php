<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_abonnement_adherent', function (Blueprint $table) {
            $table->integer('discount_percentage')->nullable()->default(null)->after('avantages');
        });
    }

    public function down(): void
    {
        Schema::table('type_abonnement_adherent', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });
    }
};
