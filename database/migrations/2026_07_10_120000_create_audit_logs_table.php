<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('user_role')->default('unknown'); // Snapshot of role at time of action
            $table->string('action'); // 'create', 'update', 'delete', 'refund', 'cancel', 'payment', etc.
            $table->string('model_type'); // 'Reservation', 'Commande', 'Subscription', etc.
            $table->unsignedBigInteger('model_id'); // ID of the affected model
            $table->string('description')->nullable(); // Human-readable summary
            $table->json('old_values')->nullable(); // Previous values (for updates)
            $table->json('new_values')->nullable(); // New values (for updates/creates)
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('timestamp');

            // Indexing for fast queries
            $table->index(['user_id', 'timestamp']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'timestamp']);
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
