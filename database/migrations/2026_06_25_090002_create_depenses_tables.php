<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_depenses', function (Blueprint $table) {
            $table->id();
            $table->string('designation_ty_dep');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->date('date_depense');
            $table->decimal('montant_dep', 12, 3);
            $table->text('commentaire_dep')->nullable();
            $table->unsignedBigInteger('type_depense_id');
            $table->unsignedBigInteger('complexe_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('type_depense_id')->references('id')->on('type_depenses')->onDelete('restrict');
            $table->foreign('complexe_id')->references('id')->on('complexes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->index(['complexe_id', 'date_depense']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
        Schema::dropIfExists('type_depenses');
    }
};
