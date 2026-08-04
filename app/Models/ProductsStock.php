<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductsStock extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'product_id', 'store', 'locations', 'company_id',
        'stock', 'ordered_stock', 'committed_stock',
    ];

    protected $casts = [
        'stock' => 'double',
        'ordered_stock' => 'double',
        'committed_stock' => 'double',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store', 'code');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'locations', 'code');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('product_id', $this->product_id)
                     ->where('store', $this->store)
                     ->where('locations', $this->locations)
                     ->where('company_id', $this->company_id);
    }
}
