<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('role', 'gerant')->get();
if ($users->isEmpty()) {
    echo "NO_GERANTS\n";
    exit(0);
}

foreach ($users as $user) {
    echo "EMAIL=" . $user->email . "\n";
    echo "ROLE=" . $user->role . "\n";
    echo "ACTIVE=" . ($user->is_active ? 'yes' : 'no') . "\n";
    echo "VERIFIED=" . ($user->email_verified_at ? 'yes' : 'no') . "\n";
    echo "---\n";
}
