<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'plan',
        'starts_at',
        'expires_at',
        'status',
        'features',
        'payment_method',
        'payment_id',
        'amount',
        'currency',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Constantes de planes
     */
    const PLAN_TRIAL = 'trial';
    const PLAN_MONTHLY = 'monthly';
    const PLAN_ANNUAL = 'annual';
    const PLAN_LIFETIME = 'lifetime';

    /**
     * Constantes de estado
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Configuración de planes y features
     */
    public static function getPlanConfig($plan = null)
    {
        $plans = [
            self::PLAN_TRIAL => [
                'name' => 'Trial',
                'duration_days' => 7,
                'price' => 0,
                'features' => [
                    'sync_products' => true,
                    'sync_customers' => true,
                    'sync_sellers' => true,
                    'sync_categories' => true,
                    'sync_quotes' => true,  // Habilitado para pruebas
                    'manage_companies' => true,
                    'advanced_reports' => false,
                    'api_access' => false,
                ]
            ],
            self::PLAN_MONTHLY => [
                'name' => 'Monthly',
                'duration_days' => 30,
                'price' => 10.00,
                'features' => [
                    'sync_products' => true,
                    'sync_customers' => true,
                    'sync_sellers' => true,
                    'sync_categories' => true,
                    'sync_quotes' => true,
                    'advanced_reports' => false,
                    'api_access' => false,
                ]
            ],
            self::PLAN_ANNUAL => [
                'name' => 'Annual',
                'duration_days' => 365,
                'price' => 100.00,
                'features' => [
                    'sync_products' => true,
                    'sync_customers' => true,
                    'sync_sellers' => true,
                    'sync_categories' => true,
                    'sync_quotes' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                ]
            ],
            self::PLAN_LIFETIME => [
                'name' => 'Lifetime',
                'duration_days' => null, // No expira
                'price' => 500.00,
                'features' => [
                    'sync_products' => true,
                    'sync_customers' => true,
                    'sync_sellers' => true,
                    'sync_categories' => true,
                    'sync_quotes' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                ]
            ],
        ];

        return $plan ? $plans[$plan] : $plans;
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la compañía
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope para suscripciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('starts_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Verificar si la suscripción está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->starts_at->isPast() &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Verificar si está expirada
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Verificar si tiene una feature específica
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return !empty($features[$feature]);
    }

    /**
     * Obtener días restantes
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->expires_at === null) {
            return null; // Lifetime
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Marcar como expirada
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Suspender suscripción
     */
    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * Reactivar suscripción
     */
    public function reactivate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Extender suscripción
     */
    public function extend(int $days): void
    {
        $newExpiresAt = $this->expires_at
            ? $this->expires_at->addDays($days)
            : now()->addDays($days);

        $this->update([
            'expires_at' => $newExpiresAt,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    /**
     * Obtener feature formateada para respuesta
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'days_remaining' => $this->days_remaining,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'plan_name' => self::getPlanConfig($this->plan)['name'] ?? ucfirst($this->plan),
        ]);
    }
}
