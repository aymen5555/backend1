<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: the 2026_06_14_000000_add_super_admin_role_to_users_table migration
        // handles both the enum schema change (adding SUPER_ADMIN) and the data migration
        // (converting ADMIN -> SUPER_ADMIN). Running that data update here before the
        // schema change would fail because SUPER_ADMIN is not yet in the enum.
    }

    public function down(): void
    {
        // No-op
    }
};
