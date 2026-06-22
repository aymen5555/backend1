<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AbonnementController extends Controller
{
    public function index(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $query = Abonnement::orderByDesc('created_at');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function show(Abonnement $abonnement): JsonResponse
    {
        $this->authorizeUser($abonnement);

        return response()->json(['success' => true, 'data' => $abonnement]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:MONTHLY,YEARLY',
            'payment_method' => 'required|in:carte,especes',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $duration = $data['type'] === 'YEARLY' ? 365 : 30;
        $now = Carbon::now('Africa/Tunis');

        $lastSubscription = Abonnement::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->orderByDesc('expires_at')
            ->first();

        $startAt = $lastSubscription && $lastSubscription->expires_at && $lastSubscription->expires_at->isFuture()
            ? $lastSubscription->expires_at
            : $now;

        $expiresAt = $startAt->copy()->addDays($duration);
        $status = $data['payment_method'] === 'especes' ? 'active' : 'pending';
        $paymentStatus = $data['payment_method'] === 'especes' ? 'paid' : 'pending';

        $abonnement = Abonnement::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'status' => $status,
            'payment_method' => $data['payment_method'],
            'payment_status' => $paymentStatus,
            'price' => $data['price'],
            'start_at' => $startAt,
            'expires_at' => $expiresAt,
            'reference' => null,
        ]);

        // Do NOT change user role here — subscription is a relationship, not a role

        return response()->json(['success' => true, 'message' => 'Subscription created.', 'data' => $abonnement], 201);
    }

    public function confirmPayment(Request $request, Abonnement $abonnement): JsonResponse
    {
        $this->authorizeUser($abonnement);

        if ($abonnement->payment_method !== 'carte' || $abonnement->payment_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This subscription cannot be confirmed.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reference' => ['required', 'string', 'max:30', 'regex:/^TXN-\d{4}-\d{3,8}$/i'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $abonnement->update([
            'payment_status' => 'paid',
            'status' => 'active',
            'reference' => $request->reference,
        ]);

        // Do NOT change user role here — subscription remains a relationship

        return response()->json(['success' => true, 'message' => 'Subscription payment confirmed.', 'data' => $abonnement]);
    }

    public function cancel(Abonnement $abonnement): JsonResponse
    {
        $this->authorizeUser($abonnement);

        if (in_array($abonnement->status, ['cancelled', 'expired'], true)) {
            return response()->json(['success' => false, 'message' => 'This subscription is already closed.'], 422);
        }

        $abonnement->update([
            'status' => 'cancelled',
            'payment_status' => $abonnement->payment_status === 'paid' ? 'refunded' : $abonnement->payment_status,
        ]);

        // Do not modify user role on cancellation; subscription is stored separately.

        return response()->json(['success' => true, 'message' => 'Subscription cancelled.', 'data' => $abonnement]);
    }

    private function authorizeUser(Abonnement $abonnement): void
    {
        $user = $this->getAuthenticatedUser();
        if ($abonnement->user_id !== $user->id && !$user->isAdmin()) {
            abort(response()->json(['success' => false, 'message' => 'Forbidden.'], 403));
        }
    }

    private function getAuthenticatedUser(): User
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            abort(response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401));
        }

        return $user;
    }
}
