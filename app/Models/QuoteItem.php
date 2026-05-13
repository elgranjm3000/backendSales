<?php
// app/Models/QuoteItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\QuoteCalculatorService;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'product_id',
        'name',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'buy_tax',
        'subtotal',
        'total',
        'type_price',
        'sort_order',
        'metadata',
        'item_type'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Model events to replace triggers
     */
    protected static function boot()
    {
        parent::boot();

        // BEFORE INSERT - Validar y calcular
        static::creating(function ($item) {
            $calculator = app(QuoteCalculatorService::class);

            // Validate
            $calculator->validateQuoteItem([
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_percentage' => (float) ($item->discount_percentage ?? 0),
                'tax_percentage' => (float) ($item->tax_percentage ?? 0),
            ]);

            // Calculate totals
            $totals = $calculator->calculateQuoteItemTotals(
                (float) $item->quantity,
                (float) $item->unit_price,
                (float) ($item->discount_percentage ?? 0),
                (float) ($item->tax_percentage ?? 0)
            );

            $item->subtotal = $totals['subtotal'];
            $item->discount_amount = $totals['discount_amount'];
            $item->tax_amount = $totals['tax_amount'];
            $item->total = $totals['total'];
        });

        // BEFORE UPDATE - Recalcular si cambió algo
        static::updating(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price', 'discount_percentage', 'tax_percentage'])) {
                $calculator = app(QuoteCalculatorService::class);

                $calculator->validateQuoteItem([
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percentage' => (float) ($item->discount_percentage ?? 0),
                    'tax_percentage' => (float) ($item->tax_percentage ?? 0),
                ]);

                $totals = $calculator->calculateQuoteItemTotals(
                    (float) $item->quantity,
                    (float) $item->unit_price,
                    (float) ($item->discount_percentage ?? 0),
                    (float) ($item->tax_percentage ?? 0)
                );

                $item->subtotal = $totals['subtotal'];
                $item->discount_amount = $totals['discount_amount'];
                $item->tax_amount = $totals['tax_amount'];
                $item->total = $totals['total'];
            }
        });

        // AFTER INSERT - Actualizar totales del quote
        static::created(function ($item) {
            app(QuoteCalculatorService::class)->updateQuoteTotals($item->quote_id);
        });

        // AFTER UPDATE - Actualizar totales del quote si cambió algo relevante
        static::updated(function ($item) {
            if ($item->isDirty(['subtotal', 'discount_amount', 'tax_amount', 'total'])) {
                app(QuoteCalculatorService::class)->updateQuoteTotals($item->quote_id);
            }
        });

        // AFTER DELETE - Actualizar totales del quote
        static::deleted(function ($item) {
            app(QuoteCalculatorService::class)->updateQuoteTotals($item->quote_id);
        });
    }
}
