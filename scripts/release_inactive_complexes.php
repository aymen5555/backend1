<?php
// Usage: php release_inactive_complexes.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Complexe;
use App\Models\User;

try {
    $inactiveOwnerIds = User::where('is_active', false)->pluck('id')->toArray();

    $backup = Complexe::whereIn('owner_id', $inactiveOwnerIds)
        ->get(['id', 'owner_id', 'name'])
        ->toArray();

    $backupPath = __DIR__ . "/../storage/release_inactive_complexes_backup_" . date('Ymd_His') . ".json";
    @mkdir(dirname($backupPath), 0755, true);
    file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Perform update
    $affected = Complexe::whereIn('owner_id', $inactiveOwnerIds)->update(['owner_id' => null]);

    echo "Backup written to: $backupPath\n";
    echo "Complexes released (owner_id set to NULL): $affected\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
