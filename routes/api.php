<?php

if (!defined('TERRAIN_ROUTE')) {
    define('TERRAIN_ROUTE', 'terrains/{terrain}');
}
if (!defined('COMPLEXE_ROUTE')) {
    define('COMPLEXE_ROUTE', 'complexes/{complexe}');
}
if (!defined('RESERVATION_ROUTE')) {
    define('RESERVATION_ROUTE', 'reservations/{reservation}');
}

use App\Http\Controllers\AdminReservationController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\CategorieProduitController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\ComplexeController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ProfilFitnessController;
use App\Http\Controllers\RecommandationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TerrainController;
use App\Http\Controllers\NotationController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\VenteDirecteController;
use App\Http\Controllers\ImageUploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — PlaySpace
|--------------------------------------------------------------------------
*/

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });
});

// ── Public routes (no auth needed) ───────────────────────────────────────────
Route::get('complexes', [ComplexeController::class, 'index']);
Route::get(COMPLEXE_ROUTE, [ComplexeController::class, 'show']);
Route::get('terrains', [TerrainController::class, 'index']);
Route::get(TERRAIN_ROUTE, [TerrainController::class, 'show']);
Route::get(TERRAIN_ROUTE . '/slots', [TerrainController::class, 'slots']);

// Public activite routes
Route::get('activites', [ActiviteController::class, 'index']);
Route::get('activites/{activite}', [ActiviteController::class, 'show']);
Route::get('activites/{activite}/places', [ActiviteController::class, 'places']);

// Make subscription types discoverable by guests (public)
Route::get('abonnements/types', [App\Http\Controllers\AbonnementAdherentController::class, 'typesDisponibles']);

// ── Products & Shop System ──────────────────────────────────────────────────────
// PUBLIC — no auth needed
Route::get('produits', [ProduitController::class, 'index']);
Route::get('produits/{produit}', [ProduitController::class, 'show']);
Route::get('categories-produits', [CategorieProduitController::class, 'index']);

// ── Protected routes ──────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Complexes — GERANT & SUPER_ADMIN manage
    Route::middleware('role:super_admin,gerant')->group(function () {
        Route::post('admin/upload-image', [ImageUploadController::class, 'upload']);
        Route::post('complexes', [ComplexeController::class, 'store']);
        Route::put(COMPLEXE_ROUTE, [ComplexeController::class, 'update']);
        Route::patch(COMPLEXE_ROUTE, [ComplexeController::class, 'update']);
        Route::delete(COMPLEXE_ROUTE, [ComplexeController::class, 'destroy']);
    });

    // Courts — GERANT & SUPER_ADMIN manage
    Route::middleware('role:super_admin,gerant')->group(function () {
        Route::post('terrains', [TerrainController::class, 'store']);
        Route::put(TERRAIN_ROUTE, [TerrainController::class, 'update']);
        Route::patch(TERRAIN_ROUTE, [TerrainController::class, 'update']);
        Route::delete(TERRAIN_ROUTE, [TerrainController::class, 'destroy']);
    });

    // Reservations
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::get(RESERVATION_ROUTE, [ReservationController::class, 'show']);
    Route::put(RESERVATION_ROUTE, [ReservationController::class, 'update']);
    Route::patch(RESERVATION_ROUTE, [ReservationController::class, 'update']);
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::put(RESERVATION_ROUTE . '/cancel', [ReservationController::class, 'cancel']);
    Route::put(RESERVATION_ROUTE . '/pay', [ReservationController::class, 'pay']);
    Route::delete(RESERVATION_ROUTE, [ReservationController::class, 'destroy']);

    // Clients — GERANT & SUPER_ADMIN
    Route::middleware('role:super_admin,gerant')->group(function () {
        Route::get('clients', [ClientController::class, 'index']);
        Route::patch('clients/{client}', [ClientController::class, 'update']);

        Route::post('admin/reservations', [AdminReservationController::class, 'manualStore']);
        Route::put('admin/reservations/{reservation}/confirm-cash', [AdminReservationController::class, 'confirmCash']);
        Route::put('admin/reservations/{reservation}/confirm-payment', [AdminReservationController::class, 'confirmCardPayment']);
        Route::put('admin/reservations/{reservation}', [AdminReservationController::class, 'adminUpdate']);
        Route::delete('admin/reservations/{reservation}', [AdminReservationController::class, 'adminDestroy']);
    });

    // User Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

    // Fitness Profile
    Route::get('profile-fitness', [ProfilFitnessController::class, 'show']);
    Route::post('profile-fitness', [ProfilFitnessController::class, 'store']);
    Route::put('profile-fitness', [ProfilFitnessController::class, 'update']);

    // Recommendations
    Route::get('recommendations', [RecommandationController::class, 'index']);
    Route::get('recommendations/produits', [RecommandationController::class, 'produits']);
    Route::get('recommendations/activites', [RecommandationController::class, 'activites']);

    // Notations / Reviews
    Route::get('notations/complexe/{id}', [NotationController::class, 'forComplexe']);
    Route::get('notations/produit/{id}', [NotationController::class, 'forProduit']);
    Route::get('notations/eligibility', [NotationController::class, 'myEligibility']);
    Route::post('notations/complexe', [NotationController::class, 'storeComplexe']);
    Route::post('notations/produit', [NotationController::class, 'storeProduit']);
    Route::delete('notations/complexe/{id}', [NotationController::class, 'destroyComplexe']);
    Route::delete('notations/produit/{id}', [NotationController::class, 'destroyProduit']);

    // ── Adhérent subscription system ──────────────────────────────────────────
    // Client routes (any authenticated user)
    // NOTE: static 'types' and 'souscrire' MUST come BEFORE the legacy wildcard routes below
    Route::post('abonnements/souscrire', [App\Http\Controllers\AbonnementAdherentController::class, 'souscrire']);
    Route::get('mes-abonnements', [App\Http\Controllers\AbonnementAdherentController::class, 'mesAbonnements']);
    Route::get('abonnements-adherent/{id}', [App\Http\Controllers\AbonnementAdherentController::class, 'show']);
    Route::put('abonnement-adherents/{id}/cancel', [App\Http\Controllers\AbonnementAdherentController::class, 'cancel']);
    Route::put('abonnement-adherents/{id}/pay', [App\Http\Controllers\AbonnementAdherentController::class, 'pay']);
    Route::delete('abonnement-adherents/{id}', [App\Http\Controllers\AbonnementAdherentController::class, 'destroy']);

    // Legacy subscription management (SUBSCRIBER role)
    Route::get('abonnements', [AbonnementController::class, 'index']);
    Route::get('abonnements/{abonnement}', [AbonnementController::class, 'show']);
    Route::post('abonnements', [AbonnementController::class, 'store']);
    Route::put('abonnements/{abonnement}/confirm-payment', [AbonnementController::class, 'confirmPayment']);
    Route::put('abonnements/{abonnement}/cancel', [AbonnementController::class, 'cancel']);

    // Gerant / Super admin routes for managing subscription types & subscriptions
    Route::middleware('role:super_admin,gerant')->group(function () {
        Route::get('admin/abonnements/types', [App\Http\Controllers\AbonnementAdherentController::class, 'adminTypes']);
        Route::post('admin/abonnements/types', [App\Http\Controllers\AbonnementAdherentController::class, 'adminStoreType']);
        Route::put('admin/abonnements/types/{id}', [App\Http\Controllers\AbonnementAdherentController::class, 'adminUpdateType']);
        Route::delete('admin/abonnements/types/{id}', [App\Http\Controllers\AbonnementAdherentController::class, 'adminDeleteType']);
        Route::get('admin/abonnements/stats', [App\Http\Controllers\AbonnementAdherentController::class, 'stats']);
        Route::get('admin/abonnements-adherent', [App\Http\Controllers\AbonnementAdherentController::class, 'adminAbonnements']);
        Route::put('admin/abonnements-adherent/{id}/confirm-payment', [App\Http\Controllers\AbonnementAdherentController::class, 'adminConfirmPayment']);
        Route::put('admin/abonnements-adherent/{id}/cancel', [App\Http\Controllers\AbonnementAdherentController::class, 'adminCancel']);
        Route::delete('admin/abonnements-adherent/{id}', [App\Http\Controllers\AbonnementAdherentController::class, 'adminDestroy']);
        Route::delete('admin/abonnements-adherent/{id}', [App\Http\Controllers\AbonnementAdherentController::class, 'adminForceDelete']);
    });

    // ── Activités CLIENT ─────────────────────────────────────────────────────
    // Note: static segment 'reservations' must come BEFORE {activite} wildcard
    Route::delete('activites/reservations/{reservation}', [ActiviteController::class, 'cancelMyReservation']);
    Route::delete('activites/reservations/{reservation}/delete', [ActiviteController::class, 'deleteMyReservation']);
    Route::put('activites/reservations/{reservation}/pay', [ActiviteController::class, 'payReservation']);
    Route::post('activites/{activite}/reserver', [ActiviteController::class, 'reserver']);
    Route::get('mes-activites', [ActiviteController::class, 'mesActivites']);

    // ── Client Commands ────────────────────────────────────────────────────────
    Route::post('commandes', [CommandeController::class, 'store']);
    Route::get('mes-commandes', [CommandeController::class, 'mesCommandes']);
    Route::get('mes-commandes/{commande}', [CommandeController::class, 'show']);
    Route::delete('mes-commandes/{commande}/annuler', [CommandeController::class, 'annuler']);

    // ── Products & Shop GERANT + SUPER_ADMIN ───────────────────────────────────
    Route::middleware('role:super_admin,gerant')->group(function () {
        Route::get('admin/produits', [ProduitController::class, 'adminIndex']);
        Route::post('admin/produits', [ProduitController::class, 'store']);
        Route::put('admin/produits/{produit}', [ProduitController::class, 'update']);
        Route::delete('admin/produits/{produit}', [ProduitController::class, 'destroy']);
        Route::put('admin/produits/{produit}/stock', [ProduitController::class, 'updateStock']);

        Route::get('admin/commandes', [CommandeController::class, 'adminIndex']);
        Route::put('admin/commandes/{commande}/statut', [CommandeController::class, 'updateStatut']);
        Route::put('admin/commandes/{commande}/confirmer-paiement', [CommandeController::class, 'confirmerPaiement']);
        Route::put('admin/commandes/{commande}/annuler', [CommandeController::class, 'adminAnnuler']);

        Route::get('admin/ventes-directes', [VenteDirecteController::class, 'index']);
        Route::post('admin/ventes-directes', [VenteDirecteController::class, 'store']);

        Route::get('admin/fournisseurs', [FournisseurController::class, 'index']);
        Route::post('admin/fournisseurs', [FournisseurController::class, 'store']);
        Route::put('admin/fournisseurs/{fournisseur}', [FournisseurController::class, 'update']);
        Route::delete('admin/fournisseurs/{fournisseur}', [FournisseurController::class, 'destroy']);
    });

    // ── Categories SUPER_ADMIN only ───────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::get('admin/categories-produits', [CategorieProduitController::class, 'adminIndex']);
        Route::post('admin/categories-produits', [CategorieProduitController::class, 'store']);
        Route::put('admin/categories-produits/{categorie}', [CategorieProduitController::class, 'update']);
        Route::delete('admin/categories-produits/{categorie}', [CategorieProduitController::class, 'destroy']);
    });

    // ── Activités GERANT & SUPER_ADMIN ─────────────────────────────────────────────
    Route::middleware('role:super_admin,gerant')->group(function () {
        // static 'reservations' must come BEFORE {activite} wildcard
        Route::get('admin/activites/reservations', [ActiviteController::class, 'adminReservations']);
        Route::put('admin/activites/reservations/{reservation}/confirm', [ActiviteController::class, 'confirmReservation']);
        Route::put('admin/activites/reservations/{reservation}/cancel', [ActiviteController::class, 'cancelReservation']);
        Route::delete('admin/activites/reservations/{reservation}', [ActiviteController::class, 'destroyReservation']);

        Route::get('admin/activites', [ActiviteController::class, 'adminIndex']);
        Route::post('admin/activites', [ActiviteController::class, 'store']);
        Route::put('admin/activites/{activite}', [ActiviteController::class, 'update']);
        Route::delete('admin/activites/{activite}', [ActiviteController::class, 'destroy']);
    });

    // Super-admin only endpoints
    Route::middleware('role:super_admin')->group(function () {
        Route::get('super-admin/stats', [App\Http\Controllers\SuperAdminController::class, 'stats']);
        
        // Gerants management
        Route::post('admin/gerants', [App\Http\Controllers\SuperAdminController::class, 'createGerant']);
        Route::get('admin/gerants', [App\Http\Controllers\SuperAdminController::class, 'listGerants']);
        Route::patch('admin/gerants/{gerant}', [App\Http\Controllers\SuperAdminController::class, 'deactivateGerant']);
        Route::post('admin/gerants/{gerant}/activate', [App\Http\Controllers\SuperAdminController::class, 'activateGerant']);
        Route::put('admin/gerants/{gerant}/complexe', [App\Http\Controllers\SuperAdminController::class, 'assignComplexe']);
        Route::delete('admin/gerants/{gerant}', [App\Http\Controllers\SuperAdminController::class, 'deleteGerant']);
    });
});
