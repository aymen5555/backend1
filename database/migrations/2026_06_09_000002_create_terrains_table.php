<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terrains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complexe_id')->constrained('complexes')->cascadeOnDelete();
            $table->string('name');
            $table->string('sport_type', 50)->default('padel');
            $table->decimal('price_per_hour', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['complexe_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terrains');
    }
};
