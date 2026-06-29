<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terrains', function (Blueprint $table) {
            $table->string('image_t')->nullable()->after('is_active');
            $table->string('description_t')->nullable()->after('image_t');
            $table->integer('capacite_t')->nullable()->after('description_t');
            $table->time('heure_ouverture')->nullable()->after('capacite_t');
            $table->time('heure_fermeture')->nullable()->after('heure_ouverture');
            $table->integer('nbheures_seance')->default(1)->after('heure_fermeture');
            $table->integer('nbminute_seance')->default(0)->after('nbheures_seance');
        });
    }

    public function down(): void
    {
        Schema::table('terrains', function (Blueprint $table) {
            $table->dropColumn(['image_t', 'description_t', 'capacite_t', 'heure_ouverture', 'heure_fermeture', 'nbheures_seance', 'nbminute_seance']);
        });
    }
};
