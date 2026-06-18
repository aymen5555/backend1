<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, update existing data to lowercase
        DB::table('users')->where('role', 'CLIENT')->update(['role' => 'client']);
        DB::table('users')->where('role', 'GERANT')->update(['role' => 'gerant']);
        DB::table('users')->where('role', 'ADMIN')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'SUPER_ADMIN')->update(['role' => 'super_admin']);
        // Convert any legacy SUBSCRIBER entries back to client, since subscription is a relationship
        DB::table('users')->where('role', 'SUBSCRIBER')->update(['role' => 'client']);

        // Then update the enum to use lowercase values (only the three canonical roles)
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['client', 'gerant', 'super_admin'])->default('client')->change();
        });
    }

    public function down(): void
    {
        // Revert to uppercase
        DB::table('users')->where('role', 'client')->update(['role' => 'CLIENT']);
        DB::table('users')->where('role', 'gerant')->update(['role' => 'GERANT']);
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'SUPER_ADMIN']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['CLIENT', 'ADMIN', 'GERANT'])->change();
        });
    }
};
