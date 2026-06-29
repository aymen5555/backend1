<?php

// Usage: php clear_all_complexes.php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Complexe;
use Illuminate\Contracts\Console\Kernel;

try {
    $backup = Complexe::whereNotNull('owner_id')
        ->get(['id', 'owner_id', 'name'])
        ->toArray();

    $backupPath = __DIR__.'/../storage/clear_all_complexes_backup_'.date('Ymd_His').'.json';
    @mkdir(dirname($backupPath), 0755, true);
    file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Set all owner_id to null
    $affected = Complexe::whereNotNull('owner_id')->update(['owner_id' => null]);

    echo "Backup written to: $backupPath\n";
    echo "Complexes cleared (owner_id set to NULL): $affected\n";
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
    exit(1);
}

return 0;
