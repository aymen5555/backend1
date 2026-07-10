<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$email = $argv[1] ?? 'battleon59@gmail.com';
$user = User::where('email', $email)->first();
if (! $user) {
    echo "user not found for {$email}\n";
    exit(0);
}

echo "id: " . $user->id . PHP_EOL;
echo "email: " . $user->email . PHP_EOL;
echo "role: " . $user->role . PHP_EOL;
echo "email_verified_at: " . ($user->email_verified_at ?? 'NULL') . PHP_EOL;
echo "created_at: " . ($user->created_at ?? 'NULL') . PHP_EOL;

// show recent notifications count
try {
    $count = $user->notifications()->count();
    echo "notifications_count: " . $count . PHP_EOL;
} catch (Exception $e) {
    echo "notifications_count: error\n";
}
