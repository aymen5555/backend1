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
        Schema::create('notation_complexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'complexe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notation_complexes');
    }
};
