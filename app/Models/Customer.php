<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'document_type', 'document_number',
        'address', 'city', 'state', 'zip_code', 'latitude', 'longitude',
        'status', 'additional_info'
    ];

    protected $casts = [
        'additional_info' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function quotes() // Cambio de sales a quotes
    {
        return $this->hasMany(Quote::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getTotalQuotesValueAttribute() // Cambio de purchases a quotes
    {
        return $this->quotes()->whereIn('status', ['approved'])->sum('total');
    }

    public function getQuotesCountAttribute()
    {
        return $this->quotes()->count();
    }

    public function getApprovedQuotesCountAttribute()
    {
        return $this->quotes()->where('status', 'approved')->count();
    }
}

