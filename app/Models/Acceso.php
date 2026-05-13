<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acceso extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'acceso';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'id_fiscal',
        'direccion',
        'telefono',
        'zona',
        'ciudad',
        'grupo',
        'vendedor',
        'contacto',
        'estado',
        'correo_electronico',
        'api_key',
        'api_key_expires_at',
        'blocked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'api_key_expires_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    /**
     * Verificar si el API key está expirado.
     */
    public function apiKeyIsValid(): bool
    {
        return $this->api_key !== null
            && ($this->api_key_expires_at === null || $this->api_key_expires_at->isFuture());
    }

    /**
     * Revocar el API key de esta empresa.
     */
    public function revokeApiKey(): void
    {
        $this->update([
            'api_key' => null,
            'api_key_expires_at' => now(),
        ]);
    }

    /**
     * Verificar si la empresa está bloqueada.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * Bloquear la empresa (detener sincronización).
     */
    public function block(): void
    {
        $this->update(['blocked_at' => now()]);
    }

    /**
     * Desbloquear la empresa.
     */
    public function unblock(): void
    {
        $this->update(['blocked_at' => null]);
    }

    /**
     * Scope para filtrar solo no bloqueados.
     */
    public function scopeNotBlocked($query)
    {
        return $query->whereNull('blocked_at');
    }

    /**
     * Scope para filtrar solo bloqueados.
     */
    public function scopeBlocked($query)
    {
        return $query->whereNotNull('blocked_at');
    }

    /**
     * Buscar un acceso por su código
     *
     * @param string $codigo
     * @return ?Acceso
     */
    public static function findByCodigo(string $codigo): ?Acceso
    {
        return static::where('codigo', $codigo)->first();
    }

    /**
     * Buscar accesos por estado
     *
     * @param string $estado
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function byEstado(string $estado)
    {
        return static::where('estado', $estado)->get();
    }

    /**
     * Buscar accesos por vendedor
     *
     * @param string $vendedor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function byVendedor(string $vendedor)
    {
        return static::where('vendedor', $vendedor)->get();
    }

    /**
     * Buscar accesos por grupo
     *
     * @param string $grupo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function byGrupo(string $grupo)
    {
        return static::where('grupo', $grupo)->get();
    }

    /**
     * Scope para buscar por nombre o id_fiscal
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nombre', 'ilike', "%{$search}%")
                ->orWhere('id_fiscal', 'ilike', "%{$search}%")
                ->orWhere('codigo', 'ilike', "%{$search}%");
        });
    }
}
