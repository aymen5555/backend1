<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Commande;
use App\Models\ReservationActivite;

echo "=== COMMANDES WITH refund_status = succeeded ===\n";
$commandes = Commande::where('refund_status', 'succeeded')->select('id', 'statut_paiement', 'refund_status')->get();
foreach ($commandes as $c) {
    echo "ID: {$c->id}, statut_paiement: {$c->statut_paiement}, refund_status: {$c->refund_status}\n";
}

echo "\n=== RESERVATION ACTIVITES WITH refund_status = succeeded ===\n";
$activites = ReservationActivite::where('refund_status', 'succeeded')->select('id', 'statut_paiement', 'refund_status')->get();
foreach ($activites as $a) {
    echo "ID: {$a->id}, statut_paiement: {$a->statut_paiement}, refund_status: {$a->refund_status}\n";
}
