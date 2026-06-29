<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complexe_id');
            $table->string('image_g');           // URL de l'image (même système que image_c sur complexes)
            $table->string('imageKit_file_id_g')->nullable(); // ID optionnel si ImageKit
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->foreign('complexe_id')->references('id')->on('complexes')->onDelete('cascade');
            $table->index(['complexe_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeries');
    }
};
