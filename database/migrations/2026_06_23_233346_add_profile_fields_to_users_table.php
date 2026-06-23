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
        Schema::table('users', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('phone');
            $table->date('date_naissance')->nullable()->after('address');
            $table->string('sexe')->nullable()->after('date_naissance');
            $table->string('profession', 100)->nullable()->after('sexe');
            $table->string('image_url', 1000)->nullable()->after('profession');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address', 'date_naissance', 'sexe', 'profession', 'image_url']);
        });
    }
};
