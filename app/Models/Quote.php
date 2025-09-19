<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_number', 'customer_id', 'company_id', 'subtotal', 'tax',
        'discount', 'total', 'status', 'notes', 'quote_date', 'valid_until',
        'terms_conditions', 'sent_at', 'approved_at', 'metadata','user_seller_id',
        'tax_amount',     
        'discount_amount',
        'bcv_rate',
        'bcv_date'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'quote_date' => 'datetime',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'tax_amount' => 'decimal:2',      
        'discount_amount' => 'decimal:2', 
    ];

    // Estados del presupuesto
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /*public function users()
    {
        return $this->belongsTo(User::class);
    }*/

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_seller_id');
    }


    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('quote_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('quote_date', now()->month);
    }

    public function scopeValid($query)
    {
        return $query->where('valid_until', '>=', now()->toDateString())
                    ->whereIn('status', [self::STATUS_SENT, self::STATUS_DRAFT]);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now()->toDateString())
                    ->whereNotIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    // Métodos de utilidad
    public function isExpired()
    {
        return $this->valid_until && 
               Carbon::parse($this->valid_until)->isPast() && 
               !in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    public function canBeModified()
    {
        return in_array($this->status, [self::STATUS_DRAFT]);
    }

    public function canBeSent()
    {
        return $this->status === self::STATUS_DRAFT && !$this->isExpired();
    }

    public function canBeApproved()
    {
        return $this->status === self::STATUS_SENT && !$this->isExpired();
    }

    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now()
        ]);
    }

    public function reject()
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function markAsExpired()
    {
        if ($this->isExpired()) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            $quote->quote_number = 'QUOTE-' . str_pad(static::count() + 1, 8, '0', STR_PAD_LEFT);
            
            // Si no se especifica fecha de validez, por defecto 30 días
            if (!$quote->valid_until) {
                $quote->valid_until = now()->addDays(30)->toDateString();
            }
        });

        // Marcar como expirados automáticamente
        static::retrieved(function ($quote) {
            $quote->markAsExpired();
        });
    }
}