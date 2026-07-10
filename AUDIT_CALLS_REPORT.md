# AuditService Callsites Report

This file lists all `AuditService::` calls found in `backend/app/Http/Controllers` with code snippets and links.

## AbonnementAdherentController
- `AuditService::cancel` at [app/Http/Controllers/AbonnementAdherentController.php](app/Http/Controllers/AbonnementAdherentController.php#L280)

```php
	AuditService::cancel($user, 'AbonnementAdherent', $sub->id, 'Client cancelled subscription');
```

- `AuditService::payment` at [app/Http/Controllers/AbonnementAdherentController.php](app/Http/Controllers/AbonnementAdherentController.php#L328)

```php
	AuditService::payment($user, 'AbonnementAdherent', $sub->id, $sub->montant_apres_remise, $request->modalite_paiement);
```

- `AuditService::refund` (manual) at [app/Http/Controllers/AbonnementAdherentController.php](app/Http/Controllers/AbonnementAdherentController.php#L748)

```php
		AuditService::refund($user, 'AbonnementAdherent', $sub->id, ['status' => 'succeeded', 'method' => 'manual']);
```

- `AuditService::refund` (stripe) at [app/Http/Controllers/AbonnementAdherentController.php](app/Http/Controllers/AbonnementAdherentController.php#L761)

```php
		    AuditService::refund($user, 'AbonnementAdherent', $sub->id, ['status' => 'succeeded', 'reference' => $refundResponse->id, 'method' => 'stripe']);
```

- `AuditService::refund` (failed) at [app/Http/Controllers/AbonnementAdherentController.php](app/Http/Controllers/AbonnementAdherentController.php#L775)

```php
		    AuditService::refund($user, 'AbonnementAdherent', $sub->id, ['status' => 'failed', 'error' => $e->getMessage()]);
```

- `AuditService::delete` at [app/Http/Controllers/AbonnementAdherentController.php](app/Http/Controllers/AbonnementAdherentController.php#L816)

```php
	AuditService::delete($user, 'AbonnementAdherent', $sub->id, ['statut' => $sub->statut, 'user_id' => $sub->user_id]);
```

## ActiviteController
- `AuditService::payment` at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L178)

```php
	AuditService::payment(auth('api')->user(), 'ReservationActivite', $reservation->id, $montant, $request->modalite_paiement ?? 'carte');
```

- `AuditService::cancel` (client unpaid) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L345)

```php
	    AuditService::cancel($user, 'ReservationActivite', $reservation->id, 'Client cancelled unpaid card activity reservation');
```

- `AuditService::cancel` (client) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L390)

```php
	AuditService::cancel($user, 'ReservationActivite', $reservation->id, 'Client cancelled activity reservation');
```

- `AuditService::refund` (manual) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L422)

```php
		AuditService::refund($user, 'ReservationActivite', $reservation->id, ['status' => 'succeeded', 'method' => 'manual']);
```

- `AuditService::refund` (stripe) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L436)

```php
		AuditService::refund($user, 'ReservationActivite', $reservation->id, ['status' => $refundStatus, 'reference' => $refundResult['id'] ?? null, 'method' => 'stripe']);
```

- `AuditService::refund` (failed) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L443)

```php
		AuditService::refund($user, 'ReservationActivite', $reservation->id, ['status' => 'failed', 'error' => $e->getMessage()]);
```

- `AuditService::payment` (admin) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L619)

```php
	AuditService::payment($user, 'ReservationActivite', $reservation->id, (float) ($request->montant ?? $reservation->montant_paye), $request->modalite_paiement);
```

- `AuditService::cancel` (admin) at [app/Http/Controllers/ActiviteController.php](app/Http/Controllers/ActiviteController.php#L642)

```php
	AuditService::cancel($user, 'ReservationActivite', $reservation->id, 'Admin cancelled activity reservation');
```

## AdminReservationController
- `AuditService::payment` (cash) at [app/Http/Controllers/AdminReservationController.php](app/Http/Controllers/AdminReservationController.php#L219)

```php
	AuditService::payment($user, 'Reservation', $reservation->id, $given, 'especes');
```

- `AuditService::payment` (card) at [app/Http/Controllers/AdminReservationController.php](app/Http/Controllers/AdminReservationController.php#L347)

```php
	AuditService::payment($user, 'Reservation', $reservation->id, $montant, 'carte');
```

## BonEntreeController
- `AuditService::payment` at [app/Http/Controllers/BonEntreeController.php](app/Http/Controllers/BonEntreeController.php#L217)

```php
	    AuditService::payment($user, 'BonEntree', $bonEntree->id, $montant, $data['type'] ?? 'paiement');
```

## BonSortieController
- `AuditService::payment` at [app/Http/Controllers/BonSortieController.php](app/Http/Controllers/BonSortieController.php#L224)

```php
	    AuditService::payment($user, 'BonSortie', $bonSortie->id, $montant, $data['type'] ?? 'paiement');
```

## CommandeController
- `AuditService::refund` (manual) at [app/Http/Controllers/CommandeController.php](app/Http/Controllers/CommandeController.php#L488)

```php
		AuditService::refund($user, 'Commande', $commande->id, ['status' => 'succeeded', 'method' => 'manual']);
```

- `AuditService::refund` (stripe) at [app/Http/Controllers/CommandeController.php](app/Http/Controllers/CommandeController.php#L504)

```php
		AuditService::refund($user, 'Commande', $commande->id, ['status' => $refundStatus, 'reference' => $refundReference, 'method' => 'stripe']);
```

- `AuditService::refund` (failed) at [app/Http/Controllers/CommandeController.php](app/Http/Controllers/CommandeController.php#L511)

```php
		AuditService::refund($user, 'Commande', $commande->id, ['status' => 'failed', 'error' => $e->getMessage()]);
```

- `AuditService::payment` at [app/Http/Controllers/CommandeController.php](app/Http/Controllers/CommandeController.php#L679)

```php
	    AuditService::payment($user, 'Commande', $commande->id, $montant, $request->modalite_paiement);
```

- `AuditService::cancel` at [app/Http/Controllers/CommandeController.php](app/Http/Controllers/CommandeController.php#L712)

```php
	    AuditService::cancel($user, 'Commande', $commande->id, 'Admin cancelled commande');
```

## ReservationController
- `AuditService::cancel` (client unpaid) at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L295)

```php
		AuditService::cancel($user, 'Reservation', $fresh->id, 'Client cancelled unpaid card reservation');
```

- `AuditService::refund` (client cancel paid) at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L335)

```php
		AuditService::refund($user, 'Reservation', $fresh->id, ['montant' => $montant, 'reason' => 'Client cancelled paid reservation']);
```

- `AuditService::refund` (manual) at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L385)

```php
		AuditService::refund($user, 'Reservation', $reservation->id, ['status' => 'succeeded', 'method' => 'manual']);
```

- `AuditService::refund` (stripe) at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L399)

```php
		AuditService::refund($user, 'Reservation', $reservation->id, ['status' => $refundStatus, 'reference' => $refundResult['id'] ?? null, 'method' => 'stripe']);
```

- `AuditService::refund` (failed) at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L406)

```php
		AuditService::refund($user, 'Reservation', $reservation->id, ['status' => 'failed', 'error' => $e->getMessage()]);
```

- `AuditService::payment` at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L488)

```php
	    AuditService::payment($user, 'Reservation', $fresh->id, $montant, 'carte');
```

- `AuditService::delete` at [app/Http/Controllers/ReservationController.php](app/Http/Controllers/ReservationController.php#L518)

```php
	AuditService::delete(auth('api')->user(), 'Reservation', $reservation->id, [
	    'status' => $reservation->status,
	    'statut_paiement' => $reservation->statut_paiement,
	    'montant_paye' => $reservation->montant_paye,
	]);
```

## VenteDirecteController
- `AuditService::payment` (batch) at [app/Http/Controllers/VenteDirecteController.php](app/Http/Controllers/VenteDirecteController.php#L144)

```php
			AuditService::payment(
			    auth('api')->user(),
			    'VenteDirecte',
			    $ventes[0]->id,
			    collect($ventes)->sum('montant_total'),
			    $request->modalite_paiement
			);
```

- `AuditService::payment` (single) at [app/Http/Controllers/VenteDirecteController.php](app/Http/Controllers/VenteDirecteController.php#L271)

```php
	    AuditService::payment(
		auth('api')->user(),
		'VenteDirecte',
		$vente->id,
		$montantTotal,
		$request->modalite_paiement
	    )
```

---

Summary: All primary controllers handling payments/refunds/cancels have `AuditService` instrumentation in both manual and Stripe flows where applicable. If you want, I can now:

- Push a no-op commit to trigger CI and retrieve the `phpstan_report.json` artifact (recommended for triage).
- Start triaging top PHPStan errors once CI produces the report.
- Expand this report with exact surrounding context for each call (more lines) or group by audit action type.

Which next step do you want me to take?

