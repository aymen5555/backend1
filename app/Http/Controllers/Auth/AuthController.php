<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Complexe;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use App\Services\EmailVerificationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerification
    ) {}

    // ──────────────────────────────────────────────
    //  POST /api/auth/register
    // ──────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                'unique:users,email',
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:client,gerant',
            'complexe_id' => [
                Rule::requiredIf(fn () => strtolower((string) $request->role) === 'gerant'),
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (strtolower((string) $request->role) === 'gerant' && $value) {
                        $complexe = Complexe::find($value);
                        if (! $complexe) {
                            $fail('The selected complexe is not available or already assigned.');
                            return;
                        }
                        if ($complexe->owner_id) {
                            $owner = User::find($complexe->owner_id);
                            if ($owner && $owner->role === 'gerant') {
                                $fail('The selected complexe is not available or already assigned.');
                            }
                        }
                    }
                },
            ],
        ], [
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'complexe_id.required' => 'Please select an available complexe for gerant registration.',
            'complexe_id.exists' => 'The selected complexe is not available or already assigned.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => strtolower($request->email),
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'client',
            ]);

            if (strtolower((string) $request->role) === 'gerant' && $request->filled('complexe_id')) {
                $target = Complexe::where('id', $request->complexe_id)->lockForUpdate()->first();

                if (! $target) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'The selected complexe is no longer available. Please choose another complexe.',
                        'errors' => ['complexe_id' => ['The selected complexe is no longer available.']],
                    ], 422);
                }

                if ($target->owner_id) {
                    $owner = User::find($target->owner_id);
                    if ($owner && $owner->role === 'gerant') {
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => 'The selected complexe is no longer available. Please choose another complexe.',
                            'errors' => ['complexe_id' => ['The selected complexe is no longer available.']],
                        ], 422);
                    }
                }

                $target->owner_id = $user->id;
                $target->save();
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        try {
            $superAdmins = User::where('role', 'super_admin')->get();
            foreach ($superAdmins as $superAdmin) {
                $superAdmin->notify(new NewUserRegistered($user));
            }
        } catch (\Throwable $notificationException) {
            report($notificationException);
        }

        $plainToken = null;
        $verificationSent = true;

        try {
            $plainToken = $this->emailVerification->issueAndSend($user);
        } catch (\Throwable $exception) {
            report($exception);
            $verificationSent = false;
        }

        $data = [
            'user' => $this->formatUser($user),
            'verification_sent' => $verificationSent,
        ];

        if ($plainToken && $this->shouldExposeLocalVerificationLink()) {
            $data['verification_url'] = $this->emailVerification->verificationUrl($plainToken);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully. Please verify your email.',
            'data' => $data,
        ], 201);
    }

    // ──────────────────────────────────────────────
    //  POST /api/auth/forgot-password
    // ──────────────────────────────────────────────
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent. Please check your email.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send password reset link. Please verify your email address.',
        ], 422);
    }

    // ──────────────────────────────────────────────
    //  POST /api/auth/reset-password
    // ──────────────────────────────────────────────
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now sign in with your new password.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'This password reset token is invalid or has expired.',
        ], 422);
    }

    // ──────────────────────────────────────────────
    //  POST /api/auth/login
    // ──────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = [
            'email' => strtolower($request->email),
            'password' => $request->password,
        ];

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token. Please try again.',
            ], 500);
        }

        $user = JWTAuth::user();

        if (! $user->is_active) {
            JWTAuth::setToken($token)->invalidate();

            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Contact support.',
            ], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            JWTAuth::setToken($token)->invalidate();

            return response()->json([
                'success' => false,
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'Please verify your email before signing in.',
            ], 403);
        }

        // Eager-load complexe for gerant so the frontend knows their assigned complex immediately after login
        if ($user->role === 'gerant') {
            $user->load('complexe');
        }

        return response()->json([
            'success' => true,
            'message' => 'Signed in successfully.',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $this->emailVerification->verify($request->token);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Verification link is invalid or expired.',
            ], 422);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plainToken = $this->emailVerification->resend($request->email);

        $data = [];
        if ($plainToken && $this->shouldExposeLocalVerificationLink()) {
            $data['verification_url'] = $this->emailVerification->verificationUrl($plainToken);
        }

        return response()->json([
            'success' => true,
            'message' => $plainToken
                ? 'A new verification email has been sent.'
                : 'If this email needs verification, a new link has been sent.',
            'data' => $data,
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST /api/auth/logout  (protected)
    // ──────────────────────────────────────────────
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException) {
            // token already expired — still treat as logout
        }

        return response()->json([
            'success' => true,
            'message' => 'Signed out successfully.',
            'data' => null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST /api/auth/refresh  (protected)
    // ──────────────────────────────────────────────
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
        } catch (JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'Token cannot be refreshed. Please sign in again.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully.',
            'data' => [
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  GET /api/auth/me  (protected)
    // ──────────────────────────────────────────────
    public function me(): JsonResponse
    {
        $user = JWTAuth::user();
        // Eager-load complexe for gerant so dashboard knows their assigned complex
        if ($user->role === 'gerant') {
            $user->load('complexe');
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile loaded successfully.',
            'data' => ['user' => $this->formatUser($user)],
        ]);
    }

    // ──────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────
    private function formatUser(User $user): array
    {
        $data = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'address' => $user->address,
            'date_naissance' => $user->date_naissance ? (is_string($user->date_naissance) ? $user->date_naissance : $user->date_naissance->toDateString()) : null,
            'sexe' => $user->sexe,
            'profession' => $user->profession,
            'image_url' => $user->image_url,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];

        // Include complexe data for gerant so frontend can display their complex
        if ($user->role === 'gerant' && $user->relationLoaded('complexe')) {
            $data['complexe_id'] = $user->complexe?->id;
            $data['complexe'] = $user->complexe ? [
                'id' => $user->complexe->id,
                'name' => $user->complexe->name,
            ] : null;
        }

        return $data;
    }

    private function shouldExposeLocalVerificationLink(): bool
    {
        return app()->environment('local') && config('mail.default') === 'log';
    }
}
