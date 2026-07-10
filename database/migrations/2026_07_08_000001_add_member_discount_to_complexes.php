<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complexes', function (Blueprint $table) {
            $table->integer('member_discount_percentage')->nullable()->after('is_active')->comment('Per-complexe member discount percentage (0-100)');
        });
    }

    public function down(): void
    {
        Schema::table('complexes', function (Blueprint $table) {
            $table->dropColumn('member_discount_percentage');
        });
    }
};
