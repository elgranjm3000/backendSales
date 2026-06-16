<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\GenericStatus;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'address',
        'phone',
        'logo',
        'logo_type',
        'email',
        'contact',
        'key_system_items_id',
        'serial_no',
        'restaurant_image',
        'restaurant_image_type',
        'main_image',
        'main_image_type',
        'status',
        'rif',
        'offline_token_hours',
        'app_version_chrystal',
        'uuid_hard_drive'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => GenericStatus::class,
    ];

    /**
     * Constantes para estado
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Relación con el usuario propietario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con vendedores de la compañía
     */
    public function sellers()
    {
        return $this->hasMany(Seller::class);
    }

    /**
     * Scope para compañías activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', GenericStatus::ACTIVE->value);
    }

    /**
     * Verificar si la compañía está activa
     */
    public function isActive()
    {
        return $this->status === GenericStatus::ACTIVE;
    }

    /**
     * Obtener vendedores activos de la compañía
     */
    public function activeSellers()
    {
        return $this->sellers()->where('seller_status', GenericStatus::ACTIVE->value);
    }

    /**
     * Contar vendedores de la compañía
     */
    public function sellersCount()
    {
        return $this->sellers()->count();
    }

    public function keySystemItem()
    {
        return $this->belongsTo(KeySystemItem::class, 'key_system_items_id');
    }
}