<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Enums\QuoteStatus;
use App\Services\QuoteCalculatorService;

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
        'bcv_date',
    ];

    protected $casts = [
        'subtotal' => 'decimal:6',
        'tax' => 'decimal:6',
        'discount' => 'decimal:6',
        'total' => 'decimal:6',
        'quote_date' => 'datetime',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'tax_amount' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'status' => QuoteStatus::class,
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
                    ->whereIn('status', [QuoteStatus::SENT->value, QuoteStatus::DRAFT->value]);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now()->toDateString())
                    ->whereNotIn('status', [QuoteStatus::APPROVED->value, QuoteStatus::REJECTED->value]);
    }

    // Métodos de utilidad
    public function isExpired()
    {
        return $this->valid_until &&
               Carbon::parse($this->valid_until)->isPast() &&
               !in_array($this->status->value, [QuoteStatus::APPROVED->value, QuoteStatus::REJECTED->value]);
    }

    public function canBeModified()
    {
        return $this->status === QuoteStatus::DRAFT;
    }

    public function canBeSent()
    {
        return $this->status === QuoteStatus::DRAFT && !$this->isExpired();
    }

    public function canBeApproved()
    {
        return $this->status === QuoteStatus::SENT && !$this->isExpired();
    }

    public function markAsSent()
    {
        $this->update([
            'status' => QuoteStatus::SENT->value,
            'sent_at' => now()
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => QuoteStatus::APPROVED->value,
            'approved_at' => now()
        ]);
    }

    public function reject()
    {
        $this->update(['status' => QuoteStatus::REJECTED->value]);
    }

    public function markAsExpired()
    {
        if ($this->isExpired()) {
            $this->update(['status' => QuoteStatus::EXPIRED->value]);
        }
    }
    
       public function sellerData()                                                                                                                 
 {                                                                                                                                            
         return $this->hasOne(Seller::class, 'user_id', 'user_seller_id');                                                                        
     }  


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            //$quote->quote_number = 'W' . str_pad(static::count() + 1, 10, '0', STR_PAD_LEFT);
            
            // 1. Obtenemos el último número de esta compañía específica
             $lastQuote = static::where('company_id', $quote->company_id)
            ->latest('id') // O 'created_at'
            ->first();

        // 2. Extraemos el número correlativo
        // Si no hay registros previos, empezamos en 1
        $nextNumber = 1;

        if ($lastQuote) {
            // Suponiendo que el formato es 'W0000000001', quitamos la 'W' y sumamos 1
            $lastNumericPart = (int) substr($lastQuote->quote_number, 1);
            $nextNumber = $lastNumericPart + 1;
        }

        // 3. Asignamos el nuevo número con el prefijo y el relleno de ceros
        $quote->quote_number = 'W' . str_pad($nextNumber, 9, '0', STR_PAD_LEFT);
            
            // Si no se especifica fecha de validez, por defecto 30 días
            if (!$quote->valid_until) {
                $quote->valid_until = now()->addDays(30)->toDateString();
            }
        });

        // Marcar como expirados automáticamente
        static::retrieved(function ($quote) {
            $quote->markAsExpired();
        });

        // Update totals if tax or discount percentage changed
        static::updating(function ($quote) {
            if ($quote->isDirty(['tax', 'discount'])) {
                app(QuoteCalculatorService::class)->updateQuoteTotals($quote->id);
            }
        });
    }
}