<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['code', 'company_id', 'description'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_store', 'code');
    }

    public function productsStock(): HasMany
    {
        return $this->hasMany(ProductsStock::class, 'store', 'code');
    }

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('code', $this->code)
                     ->where('company_id', $this->company_id);
    }
}
