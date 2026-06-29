<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs_internes', function (Blueprint $table) {
            // Nullable first so existing rows don't violate the constraint
            $table->unsignedBigInteger('complexe_id')->nullable()->after('id');
            $table->foreign('complexe_id')
                  ->references('id')->on('complexes')
                  ->onDelete('cascade');
        });

        // Backfill: assign existing fournisseurs to the complexe of their first bon_entree
        $rows = DB::table('fournisseurs_internes')->whereNull('complexe_id')->get();
        foreach ($rows as $f) {
            $be = DB::table('bon_entrees')
                ->where('fournisseur_interne_id', $f->id)
                ->orderBy('id')
                ->first();
            if ($be) {
                DB::table('fournisseurs_internes')
                    ->where('id', $f->id)
                    ->update(['complexe_id' => $be->complexe_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('fournisseurs_internes', function (Blueprint $table) {
            $table->dropForeign(['complexe_id']);
            $table->dropColumn('complexe_id');
        });
    }
};
