<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::where('email', 'superadmin@example.com')
    ->orWhere('email', 'aymencharf55@gmail.com')
    ->get();

foreach ($users as $user) {
    echo $user->id . ' | ' . $user->email . ' | ' . $user->role . ' | verified=' . ($user->email_verified_at ? 'yes' : 'no') . "\n";
}
