<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Société
        Schema::create('societes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_soc');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->date('date_de_creation')->nullable();
            $table->timestamps();
        });

        // Dirigeant
        Schema::create('dirigeants', function (Blueprint $table) {
            $table->id();
            $table->string('nom_dir');
            $table->string('image')->nullable();
            $table->unsignedBigInteger('societe_id');
            $table->timestamps();

            $table->foreign('societe_id')->references('id')->on('societes')->onDelete('cascade');
        });

        // Add societe_id FK nullable to complexes (no breaking change)
        Schema::table('complexes', function (Blueprint $table) {
            $table->unsignedBigInteger('societe_id')->nullable()->after('owner_id');
            $table->foreign('societe_id')->references('id')->on('societes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('complexes', function (Blueprint $table) {
            $table->dropForeign(['societe_id']);
            $table->dropColumn('societe_id');
        });
        Schema::dropIfExists('dirigeants');
        Schema::dropIfExists('societes');
    }
};
