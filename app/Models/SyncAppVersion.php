<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SyncAppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'typeapp',
        'status',
        'notes',
    ];

    /**
     * Scope para obtener solo versiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para obtener solo versiones inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope para filtrar por tipo de app
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('typeapp', $type);
    }

    /**
     * Scope para mobile
     */
    public function scopeMobile($query)
    {
        return $query->where('typeapp', 'mobile');
    }

    /**
     * Scope para laravel
     */
    public function scopeLaravel($query)
    {
        return $query->where('typeapp', 'laravel');
    }
}
