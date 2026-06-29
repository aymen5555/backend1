<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\ProfilFitness;
use App\Services\RecommandationService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class RecommandationController extends Controller
{
    public function __construct(
        private readonly RecommandationService $recommandationSvc
    ) {}

    /**
     * GET /api/recommendations
     * Return top-3 recommendations for the authenticated user.
     * If no profile exists, returns top complexes by rating instead.
     */
    public function index(): JsonResponse
    {
        $user = JWTAuth::user();
        $result = $this->recommandationSvc->generate($user);
        $profile = ProfilFitness::where('user_id', $user->id)->first();

        foreach ($result['recommendations'] as &$recommendation) {
            if (empty($recommendation['complexe']['id'])) {
                continue;
            }

            $complexe = Complexe::with('terrains')->withCount('terrains')->find($recommendation['complexe']['id']);
            if (! $complexe) {
                continue;
            }

            $recommendation['complexe'] = $complexe;
            $matchedTerrain = $profile
                ? $complexe->terrains->first(function ($t) use ($profile) {
                    return strtolower(trim((string) $t->sport_type)) === strtolower(trim($profile->sport_prefere));
                })
                : null;

            if ($profile && $matchedTerrain) {
                $recommendation['matched_sport'] = $matchedTerrain->sport_type;
                $recommendation['explication'] = "Ce complexe propose du {$profile->sport_prefere}, votre sport préféré, avec une note de {$complexe->moyenne_notation_c}/5.";
            } elseif ($profile) {
                $recommendation['matched_sport'] = null;
                $recommendation['explication'] = "Complexe bien noté avec une moyenne de {$complexe->moyenne_notation_c}/5.";
            }
        }
        unset($recommendation);

        return response()->json([
            'success' => true,
            'has_profile' => $result['has_profile'],
            'recommendations' => $result['recommendations'],
        ]);
    }

    /**
     * GET /api/recommendations/produits
     */
    public function produits(): JsonResponse
    {
        $user = JWTAuth::user();
        $result = $this->recommandationSvc->generateProduits($user);

        return response()->json([
            'success' => true,
            'has_profile' => $result['has_profile'],
            'recommendations' => $result['recommendations'],
        ]);
    }

    /**
     * GET /api/recommendations/activites
     */
    public function activites(): JsonResponse
    {
        $user = JWTAuth::user();
        $result = $this->recommandationSvc->generateActivites($user);

        return response()->json([
            'success' => true,
            'has_profile' => $result['has_profile'],
            'recommendations' => $result['recommendations'],
        ]);
    }
}
