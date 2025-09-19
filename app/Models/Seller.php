<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
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
        'code',
        'description',
        'status',
        'percent_sales',
        'percent_receivable',
        'inkeeper',
        'user_code',
        'percent_gerencial_debit_note',
        'percent_gerencial_credit_note',
        'percent_returned_check',
        'seller_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'percent_sales' => 'double',
        'percent_receivable' => 'double',
        'percent_gerencial_debit_note' => 'double',
        'percent_gerencial_credit_note' => 'double',
        'percent_returned_check' => 'double',
        'inkeeper' => 'boolean',
    ];

    /**
     * Constantes para estado
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la compañía
     */
    public function company()
    {
        //return $this->hasMany(Company::class);
       return $this->belongsTo(Company::class); 
    }

    /**
     * Scope para vendedores activos
     */
    public function scopeActive($query)
    {
        return $query->where('seller_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para filtrar por compañía
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Verificar si el vendedor está activo
     */
    public function isActive()
    {
        return $this->seller_status === self::STATUS_ACTIVE;
    }

    /**
     * Verificar si es posadero/encargado
     */
    public function isInkeeper()
    {
        return $this->inkeeper;
    }

    /**
     * Obtener el total de comisiones configuradas
     */
    public function getTotalCommissionPercentage()
    {
        return $this->percent_sales + $this->percent_receivable;
    }
}