<?php

namespace App\Services;

use App\Mail\VerifyEmailMail;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    private const TOKEN_PREFIX = 'psv_';

    public function issueAndSend(User $user): string
    {
        EmailVerificationToken::where('user_id', $user->id)->delete();

        $plainToken = self::TOKEN_PREFIX.Str::random(48);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
        ]);

        $verifyUrl = $this->verificationUrl($plainToken);

        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $verifyUrl));
        } catch (\Throwable $exception) {
            Log::warning('Verification email could not be sent.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $plainToken;
    }

    public function verificationUrl(string $plainToken): string
    {
        return rtrim(env('FRONTEND_URL', 'http://localhost:4200'), '/')
            .'/auth/verify-email?token='.urlencode($plainToken);
    }

    public function verify(string $plainToken): ?User
    {
        if (! str_starts_with($plainToken, self::TOKEN_PREFIX)) {
            return null;
        }

        $record = EmailVerificationToken::where('token_hash', hash('sha256', $plainToken))
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return null;
        }

        $user = $record->user;
        $user->forceFill(['email_verified_at' => now()])->save();

        EmailVerificationToken::where('user_id', $user->id)->delete();

        return $user;
    }

    public function resend(string $email): ?string
    {
        $user = User::where('email', strtolower($email))->first();

        if (! $user || $user->email_verified_at) {
            return null;
        }

        return $this->issueAndSend($user);
    }
}
