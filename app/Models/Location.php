<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['code', 'company_id', 'description', 'parent_store'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'parent_store', 'code');
    }

    public function productsStock(): HasMany
    {
        return $this->hasMany(ProductsStock::class, 'locations', 'code');
    }

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('code', $this->code)
                     ->where('company_id', $this->company_id);
    }
}
