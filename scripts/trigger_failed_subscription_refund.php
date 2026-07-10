<?php

// One-off script to create a Stripe test PaymentIntent, then call the
// AbonnementAdherentController::souscrire path with an invalid request so
// the controller auto-refunds the payment intent. Prints PI id, controller
// response, recent laravel.log lines, and whether an AbonnementAdherent row
// was created.

$autoload = __DIR__ . '/../vendor/autoload.php';
if (! file_exists($autoload)) {
    // fallback when running from project root
    $autoload = __DIR__ . '/../../vendor/autoload.php';
}
require $autoload;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\TypeAbonnementAdherent;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Find or create a test user and subscription type
$user = User::where('email', 'auto-refund-test@example.test')->first();
if (! $user) {
    $user = User::create([
        'first_name' => 'AutoRefund',
        'last_name' => 'Tester',
        'email' => 'auto-refund-test@example.test',
        'password' => bcrypt('secret'),
        'role' => 'client',
    ]);
}

$type = TypeAbonnementAdherent::first();
if (! $type) {
    $type = TypeAbonnementAdherent::create([
        'complexe_id' => 1,
        'nom' => 'AutoRefundType',
        'description' => 'Auto refund test',
        'nb_mois' => 1,
        'tarif' => 120.0,
        'prix_unitaire' => 120.0,
        'niveau_sportif_cible' => 'tous',
        'sport_cible' => 'padel',
        'discount_percentage' => 0,
        'active' => true,
    ]);
}

// Set user as authenticated for 'api' guard
Auth::guard('api')->setUser($user);

// Create and confirm a Stripe PaymentIntent using test card
Stripe::setApiKey(config('services.stripe.secret'));

try {
    // Use Stripe's test PaymentMethod identifier 'pm_card_visa' to avoid sending raw card data
    $pi = PaymentIntent::create([
        'amount' => 12000, // amount in cents
        'currency' => config('services.stripe.currency', 'eur'),
        'payment_method' => 'pm_card_visa',
        'confirm' => true,
        'description' => 'Test auto-refund payment intent',
        'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
    ]);
} catch (\Throwable $e) {
    echo "Failed creating PaymentIntent: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$piId = $pi->id;
echo "Created PaymentIntent: {$piId}\n";

// Now craft a request that will fail validation: omit date_debut
$input = [
    'type_abonnement_id' => $type->id,
    'modalite_paiement' => 'carte',
    // 'date_debut' => missing to force validator failure
    'reference' => $piId,
];

$request = Request::create('/api/abonnements/souscrire', 'POST', $input);
// Ensure Laravel's request helper returns this
$request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($input));

// Call controller method directly
$controller = new App\Http\Controllers\AbonnementAdherentController();
try {
    $response = $controller->souscrire($request);
} catch (\Throwable $e) {
    echo "Controller threw exception: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

echo "Controller response status: " . $response->getStatusCode() . PHP_EOL;
echo "Controller response body: " . $response->getContent() . PHP_EOL;

// Check DB for any AbonnementAdherent with stripe_payment_intent_id = $piId
$exists = DB::table('abonnements_adherent')->where('stripe_payment_intent_id', $piId)->exists();

echo "Abonnement row exists with stripe_payment_intent_id? " . ($exists ? 'YES' : 'NO') . PHP_EOL;

// Tail the last 200 lines of laravel.log looking for refund messages
$log = file("storage/logs/laravel.log");
$tail = array_slice($log, -200);
echo "---- last log lines ----\n";
foreach ($tail as $line) {
    if (stripos($line, 'refund') !== false || stripos($line, $piId) !== false || stripos($line, 'Auto-refund') !== false) {
        echo $line;
    }
}

echo "---- done ----\n";

