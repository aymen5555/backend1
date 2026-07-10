Discounts and Adherent perks
=============================

This file documents the two distinct discount mechanisms used by the backend.

1) Plan-level discount (TypeAbonnementAdherent.discount_percentage)
- Applies to subscription pricing only (the sale price of a plan).
- Computed by `PricingService::calculateAbonnementPricing()` and stored on the `AbonnementAdherent` as `remise` and `montant_apres_remise`.
- Example: a plan with `tarif = 200` and `discount_percentage = 25` produces `remise = 50` and `montant_apres_remise = 150`.

2) Adherent/member discount (isAdherentAt)
- Applies to bookings (courts, activities, reservations) and is a membership benefit.
- Pricing is computed by `PricingService::calculate()` and `calculateFlat()` which apply a multiplier of `0.80` when `User::isAdherentAt($complexeId)` returns true.
- This is intentionally separate from plan discounts: a user may have a plan discount for subscription purchases while the adherent discount affects per-reservation pricing.

Recommendations
- UI should surface both values where relevant: show `Remise (%)` for subscription plans, and show `Prix adhérent` / `Remise adhérent` on checkout if the current user benefits from a membership.
- Keep subscription discounts non-destructive (store the computed `remise` at creation) to avoid future recomputation changes.
- Consider making the adherent multiplier configurable per `Complexe` if required (add `member_discount_percentage` on `complexes` and use it in `PricingService`).
 - The project now supports a per-complexe configurable member discount via the `member_discount_percentage` integer column on the `complexes` table. When set, this percentage is used instead of the hard-coded 20%.

Where to look
- `app/Services/PricingService.php`
- `app/Http/Controllers/AbonnementAdherentController.php`
- `app/Http/Controllers/ActiviteController.php` and `ReservationController.php` for usage sites
