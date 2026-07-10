<?php
/**
 * Quick payment state checker — run with:
 *   php check_payment_state.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n===== COURT RESERVATIONS (last 5) =====\n";
$rows = DB::table('reservations')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'status', 'statut_paiement', 'modalite_paiement', 'montant_paye', 'created_at']);
foreach ($rows as $r) {
    echo "  ID={$r->id}  status={$r->status}  paiement={$r->statut_paiement}  modalite={$r->modalite_paiement}  montant={$r->montant_paye}\n";
}

echo "\n===== REGLEMENT RESERVATIONS (last 5) =====\n";
$tables = DB::select("SHOW TABLES LIKE 'reglement_reservations'");
if (!empty($tables)) {
    $rows = DB::table('reglement_reservations')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();
    foreach ($rows as $r) {
        $ref = $r->reference ?? 'N/A';
        echo "  ID={$r->id}  reservation_id={$r->reservation_id}  montant={$r->montant}  ref={$ref}\n";
    }
} else {
    echo "  (table reglement_reservations not found)\n";
}

echo "\n===== ACTIVITY RESERVATIONS (last 5) =====\n";
$rows = DB::table('reservation_activites')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'statut', 'statut_paiement', 'modalite_paiement', 'montant_paye', 'reference_paiement', 'created_at']);
foreach ($rows as $r) {
    echo "  ID={$r->id}  statut={$r->statut}  paiement={$r->statut_paiement}  modalite={$r->modalite_paiement}  montant={$r->montant_paye}  ref={$r->reference_paiement}\n";
}

echo "\n===== SUBSCRIPTIONS/ABONNEMENTS (last 5) =====\n";
$rows = DB::table('abonnements_adherent')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'statut', 'paye', 'reste_a_payer', 'montant_apres_remise', 'created_at']);
foreach ($rows as $r) {
    echo "  ID={$r->id}  statut={$r->statut}  paye=" . ($r->paye ? 'true' : 'false') . "  reste={$r->reste_a_payer}  montant={$r->montant_apres_remise}\n";
}

echo "\n===== SUBSCRIPTION PAYMENTS / REGLEMENTS (last 5) =====\n";
$rows = DB::table('reglements_abonnement')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'abonnement_id', 'montant', 'modalite', 'reference', 'encaisse', 'created_at']);
foreach ($rows as $r) {
    echo "  ID={$r->id}  abonnement_id={$r->abonnement_id}  montant={$r->montant}  modalite={$r->modalite}  ref={$r->reference}  encaisse=" . ($r->encaisse ? 'true' : 'false') . "\n";
}

echo "\n===== ORDERS/COMMANDES (last 5) =====\n";
$rows = DB::table('commandes')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'statut', 'statut_paiement', 'modalite_paiement', 'montant_total', 'created_at']);
foreach ($rows as $r) {
    echo "  ID={$r->id}  statut={$r->statut}  paiement={$r->statut_paiement}  modalite={$r->modalite_paiement}  montant={$r->montant_total}\n";
}

echo "\nDone.\n";
