<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ReservationActivite;

$email = 'client@playspace.tn';
$date = '2026-08-22';

$user = User::where('email', $email)->first();
if (! $user) {
    echo "user not found\n";
    exit(0);
}

$res = ReservationActivite::with('activite')
    ->where('user_id', $user->id)
    ->whereDate('date_seance', $date)
    ->get();

if ($res->isEmpty()) {
    echo "no reservation found for $email on $date\n";
    exit(0);
}

foreach ($res as $r) {
    echo "reservation id: " . $r->id . "\n";
    echo "activite id: " . ($r->activite?->id ?? 'NULL') . "\n";
    echo "activite name: " . ($r->activite?->nom ?? 'NULL') . "\n";
    echo "activite prix: " . ($r->activite?->prix ?? 'NULL') . "\n";
    echo "date_seance: " . ($r->date_seance ?? 'NULL') . "\n";
    echo "montant_paye: " . ($r->montant_paye ?? 'NULL') . "\n";
    echo "statut_paiement: " . ($r->statut_paiement ?? 'NULL') . "\n";
    echo "modalite_paiement: " . ($r->modalite_paiement ?? 'NULL') . "\n";
    echo "----\n";
}
