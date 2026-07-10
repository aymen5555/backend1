<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check column info
echo "=== COMMANDES statut_paiement column ===\n";
$result = DB::select("SHOW COLUMNS FROM commandes WHERE Field = 'statut_paiement'");
print_r($result);

echo "\n=== RESERVATION_ACTIVITES statut_paiement column ===\n";
$result = DB::select("SHOW COLUMNS FROM reservation_activites WHERE Field = 'statut_paiement'");
print_r($result);
