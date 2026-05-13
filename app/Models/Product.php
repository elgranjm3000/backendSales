<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\GenericStatus;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'price',
        'cost',
        'stock',
        'min_stock',
        'image',
        'images',
        'category_id',
        'status',
        'barcode',
        'weight',
        'attributes',
        'product_type',
        'higher_price',
        'sale_tax',
        'aliquot',
        'coin',
        'description_coin',
        'unitary_cost',
        'buy_tax',
        'buy_aliquot',
        'unidad',
        'allow_decimal'
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'cost' => 'decimal:6',
        'weight' => 'decimal:6',
        'stock' => 'decimal:6',
        'min_stock' => 'decimal:6',
        'images' => 'array',
        'attributes' => 'array',
        'status' => GenericStatus::class,
    ];

    /**
     * Relación con Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con QuoteItems
     */
    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Scope para productos activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', GenericStatus::ACTIVE->value);
    }

    /**
     * Scope para filtrar por compañía
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope para productos con stock bajo
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Scope para buscar por nombre o código
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('code', 'LIKE', "%{$term}%")
              ->orWhere('barcode', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Obtener la URL completa de la imagen principal
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/products/' . $this->image) : null;
    }

    /**
     * Obtener URLs completas de todas las imágenes
     */
    public function getImageUrlsAttribute()
    {
        if (!$this->images) return [];
        
        return collect($this->images)->map(function ($image) {
            return asset('storage/products/' . $image);
        })->toArray();
    }

    /**
     * Verificar si el producto tiene stock bajo
     */
    public function getIsLowStockAttribute()
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Calcular margen de ganancia
     */
    public function getProfitMarginAttribute()
    {
        if ($this->cost == 0) return 0;
        return (($this->price - $this->cost) / $this->cost) * 100;
    }
}