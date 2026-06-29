<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catégorie abonnement adhérent (createOrIgnore pattern)
        if (! Schema::hasTable('categorie_abonnement_adherents')) {
            Schema::create('categorie_abonnement_adherents', function (Blueprint $table) {
                $table->id();
                $table->string('nom_cat_abo_ad');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // Catégorie fournisseur externe
        if (! Schema::hasTable('categorie_fournisseurs')) {
            Schema::create('categorie_fournisseurs', function (Blueprint $table) {
                $table->id();
                $table->string('nom_cat_four');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // Catégorie ressource (terrain)
        if (! Schema::hasTable('categorie_ressources')) {
            Schema::create('categorie_ressources', function (Blueprint $table) {
                $table->id();
                $table->string('nom_cat_res');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // FK nullable sur type_abonnement_adherent
        if (! Schema::hasColumn('type_abonnement_adherent', 'categorie_abonnement_adherent_id')) {
            Schema::table('type_abonnement_adherent', function (Blueprint $table) {
                $table->unsignedBigInteger('categorie_abonnement_adherent_id')->nullable()->after('active');
                $table->foreign('categorie_abonnement_adherent_id', 'fk_type_abo_cat_abo')
                    ->references('id')
                    ->on('categorie_abonnement_adherents')
                    ->onDelete('set null');
            });
        }

        // FK nullable sur fournisseurs
        if (! Schema::hasColumn('fournisseurs', 'categorie_fournisseur_id')) {
            Schema::table('fournisseurs', function (Blueprint $table) {
                $table->unsignedBigInteger('categorie_fournisseur_id')->nullable()->after('actif');
                $table->foreign('categorie_fournisseur_id', 'fk_fournisseur_cat_four')
                    ->references('id')
                    ->on('categorie_fournisseurs')
                    ->onDelete('set null');
            });
        }

        // FK nullable sur terrains
        if (! Schema::hasColumn('terrains', 'categorie_ressource_id')) {
            Schema::table('terrains', function (Blueprint $table) {
                $table->unsignedBigInteger('categorie_ressource_id')->nullable()->after('is_active');
                $table->foreign('categorie_ressource_id', 'fk_terrain_cat_res')
                    ->references('id')
                    ->on('categorie_ressources')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('terrains', 'categorie_ressource_id')) {
            Schema::table('terrains', function (Blueprint $table) {
                $table->dropForeign('fk_terrain_cat_res');
                $table->dropColumn('categorie_ressource_id');
            });
        }
        if (Schema::hasColumn('fournisseurs', 'categorie_fournisseur_id')) {
            Schema::table('fournisseurs', function (Blueprint $table) {
                $table->dropForeign('fk_fournisseur_cat_four');
                $table->dropColumn('categorie_fournisseur_id');
            });
        }
        if (Schema::hasColumn('type_abonnement_adherent', 'categorie_abonnement_adherent_id')) {
            Schema::table('type_abonnement_adherent', function (Blueprint $table) {
                $table->dropForeign('fk_type_abo_cat_abo');
                $table->dropColumn('categorie_abonnement_adherent_id');
            });
        }
        Schema::dropIfExists('categorie_ressources');
        Schema::dropIfExists('categorie_fournisseurs');
        Schema::dropIfExists('categorie_abonnement_adherents');
    }
};
