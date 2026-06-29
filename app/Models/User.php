<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements CanResetPasswordContract, JWTSubject
{
    use CanResetPassword, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'email_verified_at',
        'is_active',
        'address',
        'date_naissance',
        'sexe',
        'profession',
        'image_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ---------- JWT ----------

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'first_name' => $this->first_name,
            'email' => $this->email,
        ];
    }

    public static function normalizeRole(?string $role): string
    {
        return match (strtolower((string) $role)) {
            'super_admin', 'admin' => 'super_admin',
            'gerant' => 'gerant',
            'client' => 'client',
            default => strtolower((string) $role),
        };
    }

    public function setRoleAttribute(?string $value): void
    {
        $this->attributes['role'] = self::normalizeRole($value);
    }

    public function getRoleAttribute(?string $value): string
    {
        return self::normalizeRole($value);
    }

    // ---------- Helpers ----------

    public function isAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->role === 'admin';
    }

    public function isGerant(): bool
    {
        return $this->role === 'gerant';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isGerantOrAdmin(): bool
    {
        return $this->isGerant() || $this->isAdmin();
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function complexes(): HasMany
    {
        return $this->hasMany(Complexe::class, 'owner_id');
    }

    public function complexe()
    {
        return $this->hasOne(Complexe::class, 'owner_id');
    }

    public function profilFitness(): HasOne
    {
        return $this->hasOne(ProfilFitness::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    public function abonnementsAdherent(): HasMany
    {
        return $this->hasMany(AbonnementAdherent::class, 'user_id');
    }

    public function hasActiveAbonnement(): bool
    {
        return $this->abonnements()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function isAdherentAt(int $complexeId): bool
    {
        return $this->abonnementsAdherent()
            ->where('complexe_id', $complexeId)
            ->where('statut', 'actif')
            ->where('date_fin', '>=', now()->toDateString())
            ->exists();
    }
}
