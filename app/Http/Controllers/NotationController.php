<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Complexe;
use App\Models\NotationComplexe;
use App\Models\NotationProduit;
use App\Models\Produit;
use App\Models\Reservation;
use App\Models\ReservationActivite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotationController extends Controller
{
    /**
     * GET /api/notations/complexe/{id}
     */
    public function forComplexe(int $complexeId): JsonResponse
    {
        $notations = NotationComplexe::with('user:id,first_name,last_name,image_url')
            ->where('complexe_id', $complexeId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notations,
        ]);
    }

    /**
     * GET /api/notations/produit/{id}
     */
    public function forProduit(int $produitId): JsonResponse
    {
        $notations = NotationProduit::with('user:id,first_name,last_name,image_url')
            ->where('produit_id', $produitId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notations,
        ]);
    }

    /**
     * POST /api/notations/complexe
     */
    public function storeComplexe(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $complexeId = $request->complexe_id;

        // Check eligibility
        if (! $this->checkComplexeEligibility($user->id, $complexeId)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas éligible à noter ce complexe. Vous devez y avoir effectué une réservation.',
            ], 403);
        }

        // Create or update review
        $notation = NotationComplexe::updateOrCreate(
            ['user_id' => $user->id, 'complexe_id' => $complexeId],
            ['note' => $request->note, 'commentaire' => $request->commentaire]
        );

        $this->updateComplexeAverageRating($complexeId);

        return response()->json([
            'success' => true,
            'message' => 'Votre avis a été enregistré avec succès.',
            'data' => $notation,
        ]);
    }

    /**
     * POST /api/notations/produit
     */
    public function storeProduit(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $produitId = $request->produit_id;

        // Check eligibility
        if (! $this->checkProduitEligibility($user->id, $produitId)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas éligible à noter ce produit. Vous devez l\'avoir acheté au préalable.',
            ], 403);
        }

        // Create or update review
        $notation = NotationProduit::updateOrCreate(
            ['user_id' => $user->id, 'produit_id' => $produitId],
            ['note' => $request->note, 'commentaire' => $request->commentaire]
        );

        return response()->json([
            'success' => true,
            'message' => 'Votre avis a été enregistré avec succès.',
            'data' => $notation,
        ]);
    }

    /**
     * DELETE /api/notations/complexe/{id}
     */
    public function destroyComplexe(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $notation = NotationComplexe::findOrFail($id);

        if ($notation->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $complexeId = $notation->complexe_id;
        $notation->delete();

        $this->updateComplexeAverageRating($complexeId);

        return response()->json([
            'success' => true,
            'message' => 'Votre avis a été supprimé.',
        ]);
    }

    /**
     * DELETE /api/notations/produit/{id}
     */
    public function destroyProduit(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $notation = NotationProduit::findOrFail($id);

        if ($notation->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $notation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre avis a été supprimé.',
        ]);
    }

    /**
     * GET /api/notations/eligibility
     */
    public function myEligibility(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['eligible' => false]);
        }

        if ($request->has('complexe_id')) {
            $complexeId = $request->input('complexe_id');
            $eligible = $this->checkComplexeEligibility($user->id, $complexeId);
            $alreadyRated = NotationComplexe::where('user_id', $user->id)
                ->where('complexe_id', $complexeId)
                ->exists();

            return response()->json([
                'eligible' => $eligible && ! $alreadyRated,
                'already_rated' => $alreadyRated,
            ]);
        }

        if ($request->has('produit_id')) {
            $produitId = $request->input('produit_id');
            $eligible = $this->checkProduitEligibility($user->id, $produitId);
            $alreadyRated = NotationProduit::where('user_id', $user->id)
                ->where('produit_id', $produitId)
                ->exists();

            return response()->json([
                'eligible' => $eligible && ! $alreadyRated,
                'already_rated' => $alreadyRated,
            ]);
        }

        // Return list of ids
        $terrainComplexes = Complexe::whereHas('terrains.reservations', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('status', 'played')
                ->where('start_at', '<', now());
        })->pluck('id')->toArray();

        $activityComplexes = Complexe::whereHas('activites.reservations', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('date_seance', '<', now()->toDateString())
                ->where(function ($sq) {
                    $sq->where('statut_paiement', 'paye')
                        ->orWhereIn('statut', ['confirmee']);
                });
        })->pluck('id')->toArray();

        $eligibleComplexes = array_unique(array_merge($terrainComplexes, $activityComplexes));
        $ratedComplexes = NotationComplexe::where('user_id', $user->id)->pluck('complexe_id')->toArray();
        $canRateComplexes = array_values(array_diff($eligibleComplexes, $ratedComplexes));

        $purchasedProducts = Produit::whereHas('ligneCommandes.commande', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('statut_paiement', 'paye');
        })->pluck('id')->toArray();
        $ratedProducts = NotationProduit::where('user_id', $user->id)->pluck('produit_id')->toArray();
        $canRateProducts = array_values(array_diff($purchasedProducts, $ratedProducts));

        return response()->json([
            'eligible_complexes' => $canRateComplexes,
            'eligible_produits' => $canRateProducts,
        ]);
    }

    private function checkComplexeEligibility(int $userId, int $complexeId): bool
    {
        $hasTerrainReservation = Reservation::where('user_id', $userId)
            ->whereHas('terrain', function ($q) use ($complexeId) {
                $q->where('complexe_id', $complexeId);
            })
            ->where('status', 'played')
            ->where('start_at', '<', now())
            ->exists();

        $hasActivityReservation = ReservationActivite::where('user_id', $userId)
            ->whereHas('activite', function ($q) use ($complexeId) {
                $q->where('complexe_id', $complexeId);
            })
            ->where(function ($q) {
                $q->where('statut_paiement', 'paye')
                    ->orWhereIn('statut', ['confirmee']);
            })
            ->exists();

        return $hasTerrainReservation || $hasActivityReservation;
    }

    private function checkProduitEligibility(int $userId, int $produitId): bool
    {
        return Commande::where('user_id', $userId)
            ->where('statut_paiement', 'paye')
            ->whereHas('lignes', function ($q) use ($produitId) {
                $q->where('produit_id', $produitId);
            })
            ->exists();
    }

    private function updateComplexeAverageRating(int $complexeId): void
    {
        $avg = NotationComplexe::where('complexe_id', $complexeId)->avg('note');
        Complexe::where('id', $complexeId)->update([
            'moyenne_notation_c' => $avg ? round($avg, 2) : 0,
        ]);
    }
}
