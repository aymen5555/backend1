<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user('api');
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        // GERANT and SUPER_ADMIN can see clients
        $query = User::query()->where('role', 'client');

        if ($user && $user->role === 'gerant') {
            // Gerant: only return clients who actually have bookings on their complexes
            $query->whereHas('reservations.terrain', fn ($c) => $c->whereIn('complexe_id', $myComplexeIds));
        }

        $query->withCount([
            'reservations as bookings_on_my_courts_count' => function ($q) use ($myComplexeIds) {
                $q->whereHas('terrain', fn ($c) => $c->whereIn('complexe_id', $myComplexeIds));
            },
        ]);

        $clients = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $u) => $this->formatClient($u));

        return response()->json(['success' => true, 'data' => $clients]);
    }

    public function update(Request $request, User $client): JsonResponse
    {
        $user = auth()->user('api');
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        if ($client->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Only client accounts can be managed here.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->role === 'gerant') {
            // Check if this client actually has bookings on the gerant's complexes
            $exists = $client->reservations()
                ->whereHas('terrain', fn ($c) => $c->whereIn('complexe_id', $myComplexeIds))
                ->exists();

            if (! $exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. This client does not belong to your complexes.',
                ], 403);
            }
        }

        $client->update(['is_active' => $request->boolean('is_active')]);

        $client->loadCount([
            'reservations as bookings_on_my_courts_count' => function ($q) use ($myComplexeIds) {
                $q->whereHas('terrain', fn ($c) => $c->whereIn('complexe_id', $myComplexeIds));
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => $client->is_active ? 'Client activated.' : 'Client deactivated.',
            'data' => $this->formatClient($client),
        ]);
    }

    private function formatClient(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at,
            'bookings_on_my_courts_count' => $user->bookings_on_my_courts_count ?? 0,
            'created_at' => $user->created_at,
        ];
    }
}
