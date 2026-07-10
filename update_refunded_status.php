<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Commande;
use App\Models\ReservationActivite;

$commandeCount = Commande::where('refund_status', 'succeeded')->update(['statut_paiement' => 'rembourse']);
$activiteCount = ReservationActivite::where('refund_status', 'succeeded')->update(['statut_paiement' => 'rembourse']);

echo "Updated $commandeCount commandes with refund_status=succeeded\n";
echo "Updated $activiteCount activity reservations with refund_status=succeeded\n";
