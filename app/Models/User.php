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
    use CanResetPassword;
    use Notifiable;

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

    /**
     * Check if user can manage a specific complex.
     * SUPER_ADMIN can manage any complex.
     * GERANT can only manage their own complexes.
     */
    public function canManageComplex(Complexe|int $complexe): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $complexeId = $complexe instanceof Complexe ? $complexe->id : $complexe;
        $ownedComplexeIds = $this->complexes()->pluck('id')->toArray();

        return in_array($complexeId, $ownedComplexeIds);
    }

    /**
     * Check if user can manage a resource in a specific complex.
     * Used for bons, products, terrains, etc. that belong to a complex.
     */
    public function canManageComplexResource(Complexe|int $complexe): bool
    {
        return $this->canManageComplex($complexe);
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

    public function reservationActivites(): HasMany
    {
        return $this->hasMany(ReservationActivite::class);
    }

    public function abonnementsAdherent(): HasMany
    {
        return $this->hasMany(AbonnementAdherent::class, 'user_id');
    }

    public function isAdherentAt(int $complexeId): bool
    {
        return $this->abonnementsAdherent()
            ->where('complexe_id', $complexeId)
            ->whereIn('statut', ['actif', 'active'])
            ->where('paye', true)
            ->where('date_fin', '>=', now()->toDateString())
            ->exists();
    }
}
