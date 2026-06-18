<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emails = ['ahmed@example.com', 'gerant@playspace.tn'];
$users = App\Models\User::whereIn('email', $emails)->get();
if ($users->isEmpty()) {
    echo "NO_USERS_FOUND\n";
}
foreach ($users as $user) {
    echo "EMAIL=" . $user->email . "\n";
    echo "ROLE=" . $user->role . "\n";
    echo "ACTIVE=" . ($user->is_active ? 'yes' : 'no') . "\n";
    echo "PASSWORD_HINT=" . (strpos($user->email, 'playspace') !== false ? 'Gerant@1234' : 'UNKNOWN') . "\n";
    echo "---\n";
}
