<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

$piId = $argv[1] ?? null;
if (! $piId) {
    echo "Usage: php trigger_refund_again.php <payment_intent_id>\n";
    exit(1);
}

$user = User::where('email', 'auto-refund-test@example.test')->first();
if (! $user) {
    echo "Test user missing.\n";
    exit(1);
}
Auth::guard('api')->setUser($user);

$input = [
    'type_abonnement_id' => 1,
    'modalite_paiement' => 'carte',
    'reference' => $piId,
    // missing date_debut to force validation
];

$request = Request::create('/api/abonnements/souscrire', 'POST', $input);
$request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($input));

$controller = new App\Http\Controllers\AbonnementAdherentController();
$response = $controller->souscrire($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo $response->getContent() . "\n";

// show last log lines referencing pi
$log = file('storage/logs/laravel.log');
$tail = array_slice($log, -200);
echo "---- recent refund-related logs ----\n";
foreach ($tail as $line) {
    if (stripos($line, $piId) !== false || stripos($line, 'Auto-refund') !== false || stripos($line, 'refund') !== false) {
        echo $line;
    }
}
