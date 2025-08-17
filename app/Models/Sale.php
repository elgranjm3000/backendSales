<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_number', 'customer_id', 'user_id', 'subtotal', 'tax',
        'discount', 'total', 'status', 'payment_method', 'payment_status',
        'notes', 'sale_date', 'metadata'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sale_date' => 'datetime',
        'metadata' => 'array'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sale_date', now()->month);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            $sale->sale_number = 'SALE-' . str_pad(static::count() + 1, 8, '0', STR_PAD_LEFT);
        });
    }
}