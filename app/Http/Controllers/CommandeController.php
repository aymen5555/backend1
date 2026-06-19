<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Complexe;
use App\Models\LigneCommande;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CommandeController extends Controller
{
    private function authorizeGerant(Complexe $complexe): void
    {
        $user = auth('api')->user();
        if ($user->role === 'gerant' && $complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'modalite_paiement' => 'required|in:especes,carte',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.produit_id' => 'required|exists:produits,id',
            'items.*.quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();
        $complexeId = $request->complexe_id;

        return DB::transaction(function () use ($request, $user, $complexeId) {
            $items = collect($request->items);
            $lignes = [];

            foreach ($items as $item) {
                $produit = Produit::with('stock')->where('id', $item['produit_id'])
                    ->where('complexe_id', $complexeId)
                    ->where('actif', true)
                    ->first();

                if (!$produit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Produit introuvable: ' . $item['produit_id'],
                    ], 422);
                }

                $stock = $produit->stock;
                if (!$stock || $stock->quantite_disponible < $item['quantite']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuffisant pour ' . $produit->nom,
                    ], 422);
                }

                $prixUnitaire = $produit->prix;
                $sousTotal = $prixUnitaire * $item['quantite'];

                $lignes[] = [
                    'produit_id' => $produit->id,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $prixUnitaire,
                    'sous_total' => $sousTotal,
                ];

                $stock->decrement('quantite_disponible', $item['quantite']);
            }

            $montantTotal = array_sum(array_column($lignes, 'sous_total'));

            $commande = Commande::create([
                'user_id' => $user->id,
                'complexe_id' => $complexeId,
                'statut' => 'en_attente',
                'statut_paiement' => 'non_paye',
                'modalite_paiement' => $request->modalite_paiement,
                'notes' => $request->notes,
                'montant_total' => $montantTotal,
            ]);

            $createdLignes = [];
            foreach ($lignes as $ligne) {
                $ligne['commande_id'] = $commande->id;
                $createdLignes[] = LigneCommande::create($ligne);
            }

            $commande->load('lignes.produit');

            $commande->lignes->each(function ($ligne) {
                $ligne->produit_nom = $ligne->produit->nom;
            });

            return response()->json([
                'success' => true,
                'data' => $commande,
            ], 201);
        });
    }

    public function mesCommandes(): JsonResponse
    {
        $user = auth('api')->user();

        $commandes = Commande::with(['complexe', 'lignes.produit'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $commandes->each(function ($commande) {
            $commande->lignes->each(function ($ligne) {
                $ligne->produit_nom = $ligne->produit->nom;
            });
        });

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    public function show(Commande $commande): JsonResponse
    {
        $user = auth('api')->user();

        if ($commande->user_id !== $user->id && !$user->isAdmin() && !$user->isGerant()) {
            return response()->json(['success' => false, 'message' => 'Accès interdit.'], 403);
        }

        $commande->load(['complexe', 'lignes.produit']);

        $commande->lignes->each(function ($ligne) {
            $ligne->produit_nom = $ligne->produit->nom;
        });

        return response()->json([
            'success' => true,
            'data' => $commande,
        ]);
    }

    public function annuler(Commande $commande): JsonResponse
    {
        $user = auth('api')->user();

        if ($commande->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Accès interdit.'], 403);
        }

        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => "Seules les commandes en attente peuvent être annulées.",
            ], 422);
        }

        return DB::transaction(function () use ($commande) {
            foreach ($commande->lignes as $ligne) {
                $stock = Stock::where('produit_id', $ligne->produit_id)->first();
                if ($stock) {
                    $stock->increment('quantite_disponible', $ligne->quantite);
                }
            }

            $commande->update(['statut' => 'annulee']);

            return response()->json(['success' => true, 'message' => 'Commande annulée.']);
        });
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Commande::with(['user:id,first_name,last_name,email', 'lignes.produit'])
            ->whereIn('complexe_id', $myComplexeIds);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('statut_paiement')) {
            $query->where('statut_paiement', $request->statut_paiement);
        }

        $commandes = $query->orderByDesc('created_at')->get();

        $commandes->each(function ($commande) {
            $commande->client_nom = $commande->user->first_name . ' ' . $commande->user->last_name;
            $commande->client_email = $commande->user->email;
            $commande->produits = $commande->lignes->map(function ($ligne) {
                return [
                    'id' => $ligne->produit->id,
                    'nom' => $ligne->produit->nom,
                    'quantite' => $ligne->quantite,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    public function updateStatut(Request $request, Commande $commande): JsonResponse
    {
        $this->authorizeGerant($commande->complexe);

        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,confirmee,preparee,livree,annulee',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $commande->update(['statut' => $request->statut]);

        return response()->json([
            'success' => true,
            'data' => $commande->fresh(),
        ]);
    }

    public function confirmerPaiement(Request $request, Commande $commande): JsonResponse
    {
        $this->authorizeGerant($commande->complexe);

        $validator = Validator::make($request->all(), [
            'modalite_paiement' => 'required|in:especes,carte',
            'reference' => 'required_if:modalite_paiement,carte|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $commande->update([
            'statut_paiement' => 'paye',
            'modalite_paiement' => $request->modalite_paiement,
        ]);

        return response()->json([
            'success' => true,
            'data' => $commande->fresh(),
        ]);
    }

    public function adminAnnuler(Commande $commande): JsonResponse
    {
        $this->authorizeGerant($commande->complexe);

        return DB::transaction(function () use ($commande) {
            foreach ($commande->lignes as $ligne) {
                $stock = Stock::where('produit_id', $ligne->produit_id)->first();
                if ($stock) {
                    $stock->increment('quantite_disponible', $ligne->quantite);
                }
            }

            $commande->update(['statut' => 'annulee']);

            return response()->json(['success' => true, 'message' => 'Commande annulée.']);
        });
    }
}