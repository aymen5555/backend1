<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Avoid running raw ALTER queries on SQLite (used in tests), which doesn't support MODIFY.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            // Running tests with SQLite in-memory DB: skip enum ALTER operations.
            return;
        }

        // Step 1: Temporarily widen the enum to include BOTH 'ADMIN' and 'SUPER_ADMIN'
        // so we can migrate data without a constraint violation.
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('CLIENT','ADMIN','SUPER_ADMIN','SUBSCRIBER','GERANT') NOT NULL DEFAULT 'CLIENT'");

        // Step 2: Migrate legacy ADMIN -> SUPER_ADMIN
        DB::table('users')
            ->where('role', 'ADMIN')
            ->update(['role' => 'SUPER_ADMIN']);

        // Step 3: Remove 'ADMIN' from the enum now that all rows use the new values
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('CLIENT','SUPER_ADMIN','SUBSCRIBER','GERANT') NOT NULL DEFAULT 'CLIENT'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        // Temporarily allow both
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('CLIENT','ADMIN','SUPER_ADMIN','SUBSCRIBER','GERANT') NOT NULL DEFAULT 'CLIENT'");

        DB::table('users')
            ->where('role', 'SUPER_ADMIN')
            ->update(['role' => 'ADMIN']);

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('CLIENT','ADMIN','SUBSCRIBER','GERANT') NOT NULL DEFAULT 'CLIENT'");
    }
};
