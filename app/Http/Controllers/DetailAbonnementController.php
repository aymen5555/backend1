<?php

namespace App\Http\Controllers;

use App\Models\DetailAbonnement;
use App\Models\TypeAbonnementAdherent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetailAbonnementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $typeId = $request->query('type_abonnement_adherent_id');
        if (! $typeId) {
            return response()->json(['success' => true, 'data' => []]);
        }
        $type = TypeAbonnementAdherent::findOrFail($typeId);
        $details = $type->detailsAbonnements()->orderBy('jour_seance')->orderBy('heure_debut_de_abo')->get();

        return response()->json(['success' => true, 'data' => $details]);
    }

    public function store(Request $request): JsonResponse
    {
        $typeId = $request->input('type_abonnement_adherent_id');
        if (! $typeId) {
            return response()->json(['success' => false, 'errors' => ['type_abonnement_adherent_id' => ['required']]], 422);
        }
        $type = TypeAbonnementAdherent::findOrFail($typeId);

        $validator = Validator::make($request->all(), [
            'jour_seance' => 'required|string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'heure_debut_de_abo' => 'required|date_format:H:i',
            'heure_fin_de_abo' => 'required|date_format:H:i|after:heure_debut_de_abo',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $detail = $type->detailsAbonnements()->create($validator->validated());

        return response()->json(['success' => true, 'data' => $detail], 201);
    }

    public function destroy($detailId): JsonResponse
    {
        $detail = DetailAbonnement::findOrFail($detailId);
        $detail->delete();

        return response()->json(['success' => true, 'message' => 'Créneau supprimé.']);
    }
}
