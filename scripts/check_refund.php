<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$piId = $argv[1] ?? null;
if (! $piId) {
    echo "Usage: php check_refund.php <payment_intent_id>\n";
    exit(1);
}

\Stripe\Stripe::setApiKey(config('services.stripe.secret'));
try {
    $refunds = \Stripe\Refund::all(['payment_intent' => $piId]);
    echo json_encode($refunds->toArray(), JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    echo "Error querying refunds: " . $e->getMessage() . PHP_EOL;
}
