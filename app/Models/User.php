<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\ActiveSession;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'status',
        'avatar',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Constantes para roles
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_COMPANY = 'company';
    const ROLE_SELLER = 'seller';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Relación con compañías que posee el usuario
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Relación con vendedores que representa el usuario
     */
    public function sellers()
    {
        return $this->hasMany(Seller::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'user_seller_id', 'id');
    }


    /**
     * Scopes para filtrar por rol
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeManagers($query)
    {
        return $query->where('role', self::ROLE_MANAGER);
    }

    public function scopeCompanies($query)
    {
        return $query->where('role', self::ROLE_COMPANY);
    }

    public function scopeSellers($query)
    {
        return $query->where('role', self::ROLE_SELLER);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Verificar si el usuario puede crear otros usuarios
     */
    public function canCreateUser($targetRole)
    {
        $permissions = [
            self::ROLE_ADMIN => [self::ROLE_MANAGER, self::ROLE_COMPANY, self::ROLE_SELLER],
            self::ROLE_MANAGER => [self::ROLE_COMPANY, self::ROLE_SELLER],
            self::ROLE_COMPANY => [self::ROLE_SELLER],
        ];

        return in_array($targetRole, $permissions[$this->role] ?? []);
    }

    /**
     * Verificar si el usuario está activo
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }


    public function activeSessions()
    {
        return $this->hasMany(ActiveSession::class);
    }

    /**
     * Verificar si el usuario tiene una sesión activa
     */
    public function hasActiveSession(): bool
    {
        return $this->activeSessions()->active()->exists();
    }

    /**
     * Obtener la sesión activa del usuario
     */
    public function getActiveSession()
    {
        return $this->activeSessions()->active()->first();
    }

    /**
     * Terminar todas las sesiones activas del usuario
     */
    public function terminateAllSessions(): void
    {
        $this->activeSessions()->delete();
        $this->tokens()->delete();
    }

    /**
     * Terminar sesión específica
     */
    public function terminateSession($tokenId): void
    {
        $this->activeSessions()->where('token_id', $tokenId)->delete();
        $this->tokens()->where('id', $tokenId)->delete();
    }
}