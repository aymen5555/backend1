<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Terrain;
use App\Models\Stock;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Run through application flows that should produce notifications and report results.
 *
 * NOTE: This command uses existing DB records where possible and will not create
 * fake users or complexes. It may skip tests when required records are missing.
 */
class TestNotifications extends Command
{
    protected $signature = 'test:notifications';

    protected $description = 'Trigger notification-producing flows using existing data and report results';

    public function handle(): int
    {
        $this->info('Starting notification tests...');

        $results = [];

        // 1) NewReservationCreated — create a reservation as an existing client for a terrain that has an owner (gérant)
        try {
            $terrain = Terrain::whereHas('complexe.owner')->first();
            $client = User::where('role', 'client')->first();

            if ($terrain && $client) {
                // set auth as client
                Auth::guard('api')->setUser($client);

                $start = now()->addDays(3)->format('Y-m-d H:i');
                $end = now()->addDays(3)->addHour()->format('Y-m-d H:i');
                $req = Request::create('/reservations', 'POST', [
                    'terrain_id' => $terrain->id,
                    'start_at' => $start,
                    'end_at' => $end,
                    'modalite_paiement' => 'carte',
                ]);

                $controller = app(\App\Http\Controllers\ReservationController::class);
                $before = DatabaseNotification::count();
                $resp = $controller->store($req);
                $after = DatabaseNotification::count();

                $created = $after > $before;
                $results['NewReservationCreated'] = [
                    'trigger' => "Client (id={$client->id}) creates reservation for terrain {$terrain->id}",
                    'recipient' => 'gerant (terrain owner)',
                    'created' => $created ? 'yes' : 'no',
                    'notes' => $this->shortNotifSummary(DatabaseNotification::latest()->first(), $created),
                ];
            } else {
                $results['NewReservationCreated'] = ['skipped' => true, 'reason' => 'Missing terrain or client'];
            }
        } catch (\Exception $e) {
            $results['NewReservationCreated'] = ['error' => $e->getMessage()];
        }

        // 2) ReservationStatusChanged — confirm cash payment as gerant (admin flow)
        try {
            $reservation = Reservation::where('modalite_paiement', 'especes')->where('statut_paiement', 'non_paye')->first();
            $gerant = null;
            if ($reservation) {
                $gerant = $reservation->terrain?->complexe?->owner;
            }

            if ($reservation && $gerant) {
                Auth::guard('api')->setUser($gerant);
                $before = DatabaseNotification::count();
                $controller = app(\App\Http\Controllers\AdminReservationController::class);
                $resp = $controller->confirmCash($reservation);
                $after = DatabaseNotification::count();
                $created = $after > $before;
                $results['ReservationStatusChanged.confirmCash'] = [
                    'trigger' => "Gerant (id={$gerant->id}) confirms cash for reservation {$reservation->id}",
                    'recipient' => "client (reservation user {$reservation->user_id})",
                    'created' => $created ? 'yes' : 'no',
                    'notes' => $this->shortNotifSummary(DatabaseNotification::latest()->first(), $created),
                ];
            } else {
                $results['ReservationStatusChanged.confirmCash'] = ['skipped' => true, 'reason' => 'No reservation with cash/unpaid found or missing gerant'];
            }
        } catch (\Exception $e) {
            $results['ReservationStatusChanged.confirmCash'] = ['error' => $e->getMessage()];
        }

        // 3) AbonnementStatusChanged — pay a subscription as client (if exists)
        try {
            $sub = \App\Models\AbonnementAdherent::where('statut', 'actif')->where('paye', false)->first();
            if ($sub) {
                $client = $sub->user;
                Auth::guard('api')->setUser($client);
                $req = Request::create('/abonnements/' . $sub->id . '/pay', 'POST', [
                    'modalite_paiement' => 'carte',
                    'reference' => 'TXN-' . now()->format('Y') . '-' . rand(1000, 9999),
                ]);
                $before = DatabaseNotification::count();
                $controller = app(\App\Http\Controllers\AbonnementAdherentController::class);
                $resp = $controller->pay($req, $sub->id);
                $after = DatabaseNotification::count();
                $created = $after > $before;
                $results['AbonnementStatusChanged.pay'] = [
                    'trigger' => "Client (id={$client->id}) pays abonnement {$sub->id}",
                    'recipient' => "client (self)",
                    'created' => $created ? 'yes' : 'no',
                    'notes' => $this->shortNotifSummary(DatabaseNotification::latest()->first(), $created),
                ];
            } else {
                $results['AbonnementStatusChanged.pay'] = ['skipped' => true, 'reason' => 'No unpaid active abonnement found'];
            }
        } catch (\Exception $e) {
            $results['AbonnementStatusChanged.pay'] = ['error' => $e->getMessage()];
        }

        // 4) LowStockAlert & OrderStatusChanged — create an order that reduces stock below threshold and then mark delivered
        try {
            $stock = Stock::whereColumn('quantite_disponible', '>', 'quantite_minimale')->with('produit.complexe.owner')->first();
            $client = User::where('role', 'client')->first();
            if ($stock && $client) {
                $owner = $stock->produit->complexe->owner ?? null;
                $complexeId = $stock->produit->complexe->id;
                $qtyToOrder = max(1, $stock->quantite_disponible - $stock->quantite_minimale);
                $payload = [
                    'complexe_id' => $complexeId,
                    'modalite_paiement' => 'especes',
                    'items' => [
                        ['produit_id' => $stock->produit_id, 'quantite' => $qtyToOrder]
                    ],
                ];

                Auth::guard('api')->setUser($client);
                $req = Request::create('/commandes', 'POST', $payload);
                $before = DatabaseNotification::count();
                $controller = app(\App\Http\Controllers\CommandeController::class);
                $resp = $controller->store($req);
                $after = DatabaseNotification::count();
                $created = $after > $before;

                $order = null;
                if ($resp instanceof \Illuminate\Http\JsonResponse) {
                    $data = $resp->getData(true);
                    $order = $data['data'] ?? null;
                }

                $results['LowStockAlert.order'] = [
                    'trigger' => "Client (id={$client->id}) creates order for produit {$stock->produit_id} qty {$qtyToOrder}",
                    'recipient' => $owner ? "gerant (id={$owner->id})" : 'gerant (none found)',
                    'created' => $created ? 'yes' : 'no',
                    'notes' => $this->shortNotifSummary(DatabaseNotification::latest()->first(), $created),
                ];

                if ($order && isset($order['id'])) {
                    $commande = \App\Models\Commande::find($order['id']);
                    if ($owner) {
                        Auth::guard('api')->setUser($owner);
                        $req2 = Request::create('/commandes/' . $commande->id . '/statut', 'PUT', ['statut' => 'livree']);
                        $before2 = DatabaseNotification::count();
                        $resp2 = $controller->updateStatut($req2, $commande);
                        $after2 = DatabaseNotification::count();
                        $created2 = $after2 > $before2;
                        $results['OrderStatusChanged.livree'] = [
                            'trigger' => "Gerant (id={$owner->id}) marks commande {$commande->id} livree",
                            'recipient' => "client (id={$commande->user_id}) and possibly gerant",
                            'created' => $created2 ? 'yes' : 'no',
                            'notes' => $this->shortNotifSummary(DatabaseNotification::latest()->first(), $created2),
                        ];
                    }
                }
            } else {
                $results['LowStockAlert.order'] = ['skipped' => true, 'reason' => 'No suitable stock or client found'];
            }
        } catch (\Exception $e) {
            $results['LowStockAlert.order'] = ['error' => $e->getMessage()];
        }

        // Print results table
        $this->line('');
        $this->info('Notification test results:');
        foreach ($results as $key => $r) {
            $this->line("- {$key}:");
            foreach ($r as $k => $v) {
                $this->line("    {$k}: {$v}");
            }
        }

        $this->line('');
        $this->info('Done. Review created notification rows in the notifications table.');

        return Command::SUCCESS;
    }

    private function shortNotifSummary($notif, $created)
    {
        if (! $created || ! $notif) {
            return 'no new notification found';
        }
        try {
            $data = is_array($notif->data) ? $notif->data : (array) $notif->data;
            $type = $data['type'] ?? $notif->type ?? 'unknown';
            $msg = $data['message'] ?? ($data['text'] ?? '');
            return "new notification id={$notif->id}, type={$type}, message={$msg}";
        } catch (\Throwable $e) {
            return 'could not summarize notification';
        }
    }
}
