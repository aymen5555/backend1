<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complexes', function (Blueprint $table) {
            $table->string('image_c')->nullable()->after('is_active');
            $table->string('website_c')->nullable()->after('image_c');
            $table->string('facebook_c')->nullable()->after('website_c');
            $table->string('instagram_c')->nullable()->after('facebook_c');
            $table->text('description_c')->nullable()->after('instagram_c');
            $table->string('email_c')->nullable()->after('instagram_c');
            $table->string('horaire_c')->nullable()->after('email_c');
            $table->decimal('latitude_c', 10, 8)->nullable()->after('horaire_c');
            $table->decimal('longitude_c', 11, 8)->nullable()->after('latitude_c');
            $table->decimal('moyenne_notation_c', 3, 2)->nullable()->after('longitude_c');
        });
    }

    public function down(): void
    {
        Schema::table('complexes', function (Blueprint $table) {
            $table->dropColumn(['image_c', 'website_c', 'facebook_c', 'instagram_c', 'description_c', 'email_c', 'horaire_c', 'latitude_c', 'longitude_c', 'moyenne_notation_c']);
        });
    }
};