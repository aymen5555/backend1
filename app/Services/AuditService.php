<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit entry for a critical action
     *
     * @param User|null $user
     * @param string $action ('create', 'update', 'delete', 'refund', 'cancel', 'payment', etc.)
     * @param string $modelType (e.g. 'Reservation', 'Commande', 'Subscription')
     * @param int $modelId
     * @param string $description
     * @param array $oldValues (null for creates)
     * @param array $newValues (null for deletes)
     */
    public static function log(
        ?User $user,
        string $action,
        string $modelType,
        int $modelId,
        string $description = '',
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'user_role' => $user?->role ?? 'unknown',
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            // Log service error but don't fail the main operation
            Log::warning('Failed to create audit log', [
                'error' => $e->getMessage(),
                'action' => $action,
                'model_type' => $modelType,
            ]);
        }
    }

    /**
     * Shorthand for logging refund actions
     */
    public static function refund(User|int|null $user, string $modelType, int $modelId, array $details = []): void
    {
        $user = $user instanceof User ? $user : (is_int($user) ? User::find($user) : null);
        $reference = $details['reference'] ?? $details['refund_reference'] ?? 'n/a';
        $method = $details['method'] ?? 'unknown';
        $status = $details['status'] ?? 'unknown';
        $descriptionParts = ["Refund processed", "status: {$status}", "method: {$method}"];

        if ($reference !== 'n/a') {
            $descriptionParts[] = "reference: {$reference}";
        }
        if (isset($details['error'])) {
            $descriptionParts[] = "error: {$details['error']}";
        }

        self::log(
            $user,
            'refund',
            $modelType,
            $modelId,
            implode(', ', $descriptionParts),
            $details,
        );
    }

    /**
     * Shorthand for logging cancellation actions
     */
    public static function cancel(User|int|null $user, string $modelType, int $modelId, string $reason = ''): void
    {
        $user = $user instanceof User ? $user : (is_int($user) ? User::find($user) : null);
        self::log(
            $user,
            'cancel',
            $modelType,
            $modelId,
            "Cancelled. Reason: {$reason}",
        );
    }

    /**
     * Shorthand for logging payment actions
     */
    public static function payment(User|int|null $user, string $modelType, int $modelId, float $amount, string $method = ''): void
    {
        $user = $user instanceof User ? $user : (is_int($user) ? User::find($user) : null);
        self::log(
            $user,
            'payment',
            $modelType,
            $modelId,
            "Payment recorded: {$amount} via {$method}",
            [
                'amount' => $amount,
                'method' => $method,
            ],
        );
    }

    /**
     * Shorthand for logging deletion
     */
    public static function delete(User|int|null $user, string $modelType, int $modelId, array $deletedData = []): void
    {
        $user = $user instanceof User ? $user : (is_int($user) ? User::find($user) : null);
        self::log(
            $user,
            'delete',
            $modelType,
            $modelId,
            "Deleted",
            $deletedData,
        );
    }
}
