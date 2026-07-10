<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Stripe\Stripe::setApiKey(config('services.stripe.secret'));

try {
    $pi = \Stripe\PaymentIntent::create([
        'amount' => 1000, // 1.000 TND in millimes
        'currency' => 'tnd',
        'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
        'description' => 'TND support check',
    ]);
    echo "Created PaymentIntent: " . $pi->id . " status=" . ($pi->status ?? 'n/a') . PHP_EOL;
    echo json_encode(['id' => $pi->id, 'status' => $pi->status, 'currency' => $pi->currency], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $e) {
    echo "Error creating TND PaymentIntent: " . $e->getMessage() . PHP_EOL;
}
