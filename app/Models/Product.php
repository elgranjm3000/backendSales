<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'price', 'cost', 'stock', 'min_stock',
        'image', 'images', 'category_id', 'status', 'barcode', 'weight', 'attributes'
    ];

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quoteItems() // Cambio de saleItems a quoteItems
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function getTimesQuotedAttribute()
    {
        return $this->quoteItems()->sum('quantity');
    }
}

