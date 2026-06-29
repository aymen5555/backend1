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
        Schema::table('terrains', function (Blueprint $table) {
            if (! Schema::hasColumn('terrains', 'image_t')) {
                $table->string('image_t')->nullable()->after('price_per_hour');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terrains', function (Blueprint $table) {
            if (Schema::hasColumn('terrains', 'image_t')) {
                $table->dropColumn('image_t');
            }
        });
    }
};
