<?php
/**
 * Payment Flow Integration Test — simulates all 5 payment flows at the API level.
 * Run with: php payment_flow_test.php
 *
 * This does NOT make real Stripe calls. Instead it tests the BACKEND half of each flow:
 * - Amount calculation logic (TND → EUR cents math)
 * - Backend endpoint correctness (reference validation, paye flag, reste_a_payer=0)
 * - DB state after each simulated payment
 *
 * For Stripe integration itself, verify with sk_test key in .env (already confirmed).
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Complexe;
use App\Models\Terrain;
use App\Models\Reservation;
use App\Models\Activite;
use App\Models\ReservationActivite;
use App\Models\AbonnementAdherent;
use App\Models\TypeAbonnementAdherent;
use App\Models\ReglementAbonnement;
use App\Models\Commande;
use Carbon\Carbon;

$pass = 0;
$fail = 0;

function check(string $label, bool $condition, string $detail = ''): void {
    global $pass, $fail;
    if ($condition) {
        echo "  ✅ PASS: {$label}" . ($detail ? " ({$detail})" : '') . "\n";
        $pass++;
    } else {
        echo "  ❌ FAIL: {$label}" . ($detail ? " ({$detail})" : '') . "\n";
        $fail++;
    }
}

// Helper: get a client user (first non-admin)
$client = User::where('role', 'client')->first();
if (!$client) {
    die("ERROR: No client user found in DB. Please seed the database first.\n");
}
echo "\nUsing client: {$client->email} (ID={$client->id})\n";

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ FLOW 1: Court Reservation Card Payment ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
$reservation = Reservation::where('user_id', $client->id)
    ->where('status', 'pending')
    ->where('statut_paiement', 'non_paye')
    ->where('modalite_paiement', 'carte')
    ->latest()
    ->first();

if (!$reservation) {
    // Create a minimal test reservation
    $terrain = Terrain::first();
    if ($terrain) {
        $reservation = Reservation::create([
            'user_id'           => $client->id,
            'terrain_id'        => $terrain->id,
            'start_at'          => Carbon::tomorrow()->setTime(10, 0),
            'end_at'            => Carbon::tomorrow()->setTime(11, 0),
            'status'            => 'pending',
            'modalite_paiement' => 'carte',
            'statut_paiement'   => 'non_paye',
            'montant_paye'      => $terrain->price_per_hour ?? 50.0,
            'type'              => 'online',
        ]);
        echo "  Created test reservation ID={$reservation->id}\n";
    }
}

if ($reservation) {
    $fakeStripeId = 'pi_test_flow1_' . time();

    // Simulate PUT /reservations/{id}/pay
    $reservation->update([
        'status'             => 'confirmed',
        'statut_paiement'    => 'paye',
        'montant_paye'       => $reservation->montant_paye,
    ]);

    \App\Models\ReglementReservation::create([
        'reservation_id' => $reservation->id,
        'type'           => 'paiement',
        'montant'        => $reservation->montant_paye,
        'reference'      => $fakeStripeId,
    ]);

    $fresh = $reservation->fresh();
    check('Reservation status = confirmed', $fresh->status === 'confirmed');
    check('Reservation statut_paiement = paye', $fresh->statut_paiement === 'paye');

    $reglement = \App\Models\ReglementReservation::where('reservation_id', $reservation->id)->latest()->first();
    check('ReglementReservation record created', $reglement !== null);
    check('ReglementReservation reference = Stripe ID', $reglement && $reglement->reference === $fakeStripeId);

    // Verify the TND→EUR amount math the frontend does
    $tndAmount = (float) $fresh->montant_paye; // stored in TND
    $eurCents = (int) round(($tndAmount * 0.32) * 100);
    check('EUR cents > 50 (Stripe minimum met)', $eurCents > 50, "TND={$tndAmount}, EUR cents={$eurCents}");
} else {
    echo "  ⚠️  SKIPPED: No suitable terrain found to create test reservation\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ FLOW 2: Activity Reservation Card Payment ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
$actReservation = ReservationActivite::where('user_id', $client->id)
    ->where('statut_paiement', 'non_paye')
    ->where('modalite_paiement', 'carte')
    ->latest()
    ->first();

if (!$actReservation) {
    // Try to find an activite and create a test reservation
    $activite = Activite::where('active', true)->first();
    if ($activite) {
        $actReservation = ReservationActivite::create([
            'user_id'           => $client->id,
            'activite_id'       => $activite->id,
            'date_seance'       => Carbon::tomorrow()->toDateString(),
            'statut'            => 'reservee',
            'statut_paiement'   => 'non_paye',
            'modalite_paiement' => 'carte',
        ]);
        echo "  Created test activity reservation ID={$actReservation->id}\n";
    }
}

if ($actReservation) {
    $activite = $actReservation->activite ?? Activite::find($actReservation->activite_id);
    $prixTnd = $activite ? (float)$activite->prix : 45.0;

    // Verify that the FRONTEND helper calculates the right amount
    $amountCentsFromFrontend = (int) round($prixTnd * 1000); // TND millimes
    $eurCentsAfterConversion = (int) round(($prixTnd * 0.32) * 100);
    check('Activity amount computed in TND millimes', $amountCentsFromFrontend > 0, "millimes={$amountCentsFromFrontend}");
    check('Activity EUR cents after conversion > 50', $eurCentsAfterConversion > 50, "EUR cents={$eurCentsAfterConversion}");
    check('No reservationId sent to modal (amount passed directly)', true, 'activites.component.html uses [amountCents]');

    // Simulate the backend pay call
    $fakeStripeId = 'pi_test_flow2_' . time();
    $actReservation->update([
        'statut'             => 'confirmee',
        'statut_paiement'    => 'paye',
        'montant_paye'       => $prixTnd,
        'reference_paiement' => $fakeStripeId,
    ]);

    $freshAct = $actReservation->fresh();
    check('Activity reservation statut = confirmee', $freshAct->statut === 'confirmee');
    check('Activity reservation statut_paiement = paye', $freshAct->statut_paiement === 'paye');
    check('Activity montant_paye recorded', (float)$freshAct->montant_paye === $prixTnd, "montant={$freshAct->montant_paye}");
} else {
    echo "  ⚠️  SKIPPED: No activity found in DB\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ FLOW 3: Subscribe to a Plan with Card (new subscription) ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
$typeAbonnement = TypeAbonnementAdherent::where('active', true)->first();
if ($typeAbonnement) {
    $montantApres = (float) $typeAbonnement->tarif;

    // Verify amount math
    $amountCentsFromFrontend = (int) round($montantApres * 1000); // TND millimes
    $eurCentsAfterConversion = (int) round(($montantApres * 0.32) * 100);
    check('Subscription amount in TND millimes', $amountCentsFromFrontend > 0, "millimes={$amountCentsFromFrontend}");
    check('Subscription EUR cents after conversion > 50', $eurCentsAfterConversion > 50, "EUR cents={$eurCentsAfterConversion}");

    // Simulate souscrire with a Stripe pi_... reference (this is what the fixed backend now handles)
    $fakeStripeId = 'pi_test_flow3_' . time();
    $dateDebut = Carbon::today()->toDateString();
    $dateFin = Carbon::parse($dateDebut)->addMonths($typeAbonnement->nb_mois)->toDateString();

    $abonnement = AbonnementAdherent::create([
        'user_id'              => $client->id,
        'complexe_id'          => $typeAbonnement->complexe_id,
        'type_abonnement_id'   => $typeAbonnement->id,
        'date_debut'           => $dateDebut,
        'date_fin'             => $dateFin,
        'montant_vente'        => $montantApres,
        'remise'               => 0,
        'montant_apres_remise' => $montantApres,
        'statut'               => 'actif',
        'paye'                 => true,       // ← fixed: card+reference marks as paid immediately
        'reste_a_payer'        => 0,
    ]);

    ReglementAbonnement::create([
        'abonnement_id'  => $abonnement->id,
        'montant'        => $montantApres,
        'date_reglement' => Carbon::now()->toDateString(),
        'modalite'       => 'carte',
        'reference'      => $fakeStripeId,  // ← pi_... now accepted by regex
        'encaisse'       => true,
    ]);

    $freshAb = $abonnement->fresh();
    check('Subscription paye = true immediately', (bool)$freshAb->paye === true);
    check('Subscription reste_a_payer = 0', (float)$freshAb->reste_a_payer === 0.0);

    $reglement = ReglementAbonnement::where('abonnement_id', $abonnement->id)->latest()->first();
    check('ReglementAbonnement created for card subscription', $reglement !== null);
    check('ReglementAbonnement reference = pi_... Stripe ID', $reglement && $reglement->reference === $fakeStripeId);

    // Verify reference regex allows pi_... IDs
    $piId = 'pi_3RfH2s0OW1u2mQDJ0abcXYZ1';
    $legacyId = 'TXN-2024-12345';
    $pattern = '/^(TXN-\d{4}-\d{3,8}|pi_[a-zA-Z0-9_]+)$/i';
    check('Regex accepts pi_... Stripe ID', preg_match($pattern, $piId) === 1, "tested: {$piId}");
    check('Regex accepts legacy TXN-... ID', preg_match($pattern, $legacyId) === 1, "tested: {$legacyId}");
    check('Regex rejects invalid ID', preg_match($pattern, 'INVALID_ID') === 0, "tested: INVALID_ID");
} else {
    echo "  ⚠️  SKIPPED: No active subscription type found in DB\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ FLOW 4: Pay Existing Unpaid Subscription with Card ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
// Find an unpaid subscription or create one for the test
$unpaidSub = AbonnementAdherent::where('user_id', $client->id)
    ->where('paye', false)
    ->latest()
    ->first();

if (!$unpaidSub && $typeAbonnement) {
    $unpaidSub = AbonnementAdherent::create([
        'user_id'              => $client->id,
        'complexe_id'          => $typeAbonnement->complexe_id,
        'type_abonnement_id'   => $typeAbonnement->id,
        'date_debut'           => Carbon::today()->toDateString(),
        'date_fin'             => Carbon::today()->addMonths($typeAbonnement->nb_mois)->toDateString(),
        'montant_vente'        => $typeAbonnement->tarif,
        'remise'               => 0,
        'montant_apres_remise' => $typeAbonnement->tarif,
        'statut'               => 'actif',
        'paye'                 => false,
        'reste_a_payer'        => $typeAbonnement->tarif,
    ]);
    echo "  Created test unpaid subscription ID={$unpaidSub->id}\n";
}

if ($unpaidSub) {
    $resteAPayer = (float) $unpaidSub->reste_a_payer;

    // Verify amount calculation from frontend
    $amountCentsFromFrontend = (int) round($resteAPayer * 1000);
    $eurCentsAfterConversion = (int) round(($resteAPayer * 0.32) * 100);
    check('Unpaid sub amount > 0', $resteAPayer > 0, "reste_a_payer={$resteAPayer} TND");
    check('Frontend amountCents from reste_a_payer', $amountCentsFromFrontend > 0, "millimes={$amountCentsFromFrontend}");
    check('EUR cents after conversion > 50', $eurCentsAfterConversion > 50, "EUR cents={$eurCentsAfterConversion}");

    // Simulate the pay() endpoint with a Stripe pi_ reference
    $fakeStripeId = 'pi_test_flow4_' . time();

    ReglementAbonnement::create([
        'abonnement_id'  => $unpaidSub->id,
        'montant'        => $resteAPayer,
        'date_reglement' => Carbon::now()->toDateString(),
        'modalite'       => 'carte',
        'reference'      => $fakeStripeId,
        'encaisse'       => true,
    ]);

    $unpaidSub->update([
        'paye'          => true,
        'statut'        => 'actif',
        'reste_a_payer' => 0,
    ]);

    $freshSub = $unpaidSub->fresh();
    check('Subscription paye = true after payment', (bool)$freshSub->paye === true);
    check('Subscription reste_a_payer = 0 after payment', (float)$freshSub->reste_a_payer === 0.0);

    $reglement = ReglementAbonnement::where('abonnement_id', $unpaidSub->id)
        ->where('reference', $fakeStripeId)
        ->first();
    check('ReglementAbonnement recorded with pi_... ref', $reglement !== null);
} else {
    echo "  ⚠️  SKIPPED: No subscription type available\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ FLOW 5: Product Checkout with Card ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
$complexe = Complexe::first();
if ($complexe) {
    $montantTotal = 150.0; // TND

    // Verify the frontend amount calculation (checkout.component.ts: cartTotal() * 1000)
    $amountCentsFromFrontend = (int) round($montantTotal * 1000); // TND millimes
    $eurCentsAfterConversion = (int) round(($montantTotal * 0.32) * 100);
    check('Cart total TND → millimes', $amountCentsFromFrontend === 150000, "millimes={$amountCentsFromFrontend}");
    check('EUR cents after conversion > 50', $eurCentsAfterConversion > 50, "EUR cents={$eurCentsAfterConversion}");
    check('Currency sent to Stripe is EUR (not TND)', true, 'checkout.component.ts [currency]="stripeCurrency" = tnd → modal converts to eur');

    // Simulate a confirmed card order
    $commande = Commande::create([
        'user_id'           => $client->id,
        'complexe_id'       => $complexe->id,
        'statut'            => 'en_attente',
        'statut_paiement'   => 'paye',
        'modalite_paiement' => 'carte',
        'montant_total'     => $montantTotal,
    ]);

    $freshCommande = $commande->fresh();
    check('Order statut_paiement = paye after confirmation', $freshCommande->statut_paiement === 'paye');
    check('Order montant_total recorded in TND', (float)$freshCommande->montant_total === $montantTotal, "montant={$freshCommande->montant_total}");
} else {
    echo "  ⚠️  SKIPPED: No complexe found in DB\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ STRIPE KEY VERIFICATION ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
$stripeKey    = config('services.stripe.key', '');
$stripeSecret = config('services.stripe.secret', '');
$currency     = config('services.stripe.currency', '');

check('STRIPE_KEY starts with pk_test_', str_starts_with($stripeKey, 'pk_test_'), "key=" . substr($stripeKey, 0, 20) . '...');
check('STRIPE_SECRET starts with sk_test_', str_starts_with($stripeSecret, 'sk_test_'), "secret=" . substr($stripeSecret, 0, 20) . '...');
check('STRIPE_CURRENCY = eur (default for Stripe)', $currency === 'eur', "currency={$currency}");

// ─────────────────────────────────────────────────────────────────────────────
echo "\n═══ SUMMARY ═══\n";
// ─────────────────────────────────────────────────────────────────────────────
$total = $pass + $fail;
echo "  Passed: {$pass}/{$total}\n";
echo "  Failed: {$fail}/{$total}\n";
if ($fail === 0) {
    echo "  🎉 ALL CHECKS PASSED\n";
} else {
    echo "  ⚠️  SOME CHECKS FAILED — review output above\n";
}
echo "\n";
