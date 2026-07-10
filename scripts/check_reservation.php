<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Reservation;

$user = User::where('email', 'client@playspace.tn')->first();
if (! $user) {
    echo "user not found\n";
    exit(0);
}

$r = Reservation::where('user_id', $user->id)->latest()->first();
if (! $r) {
    echo "reservation not found\n";
    exit(0);
}

echo "user id: " . $user->id . "\n";
echo "reservation id: " . $r->id . "\n";
echo "montant_paye: " . ($r->montant_paye ?? 'NULL') . "\n";
echo "start_at: " . ($r->start_at ?? 'NULL') . "\n";
echo "modalite_paiement: " . ($r->modalite_paiement ?? 'NULL') . "\n";
echo "statut_paiement: " . ($r->statut_paiement ?? 'NULL') . "\n";

// Additional context: terrain pricing and duration
$terrain = $r->terrain;
if ($terrain) {
    echo "terrain id: " . ($terrain->id ?? 'NULL') . "\n";
    echo "complexe id: " . ($terrain->complexe_id ?? 'NULL') . "\n";
    echo "price_per_hour: " . ($terrain->price_per_hour ?? 'NULL') . "\n";
} else {
    echo "terrain: NULL\n";
}

if (isset($r->start_at) && isset($r->end_at)) {
    $start = strtotime($r->start_at);
    $end = strtotime($r->end_at);
    $hours = ($end - $start) / 3600;
    echo "duration_hours: " . $hours . "\n";
}
