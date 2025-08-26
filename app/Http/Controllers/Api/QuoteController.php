<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::with(['customer', 'user', 'items.product']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('quote_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('quote_date', '<=', $request->date_to);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filtro por estado de expiración
        if ($request->has('expired')) {
            if ($request->expired === 'true') {
                $query->expired();
            } else {
                $query->valid();
            }
        }

        $quotes = $query->orderBy('quote_date', 'desc')
                       ->paginate($request->per_page ?? 20);

        return response()->json($quotes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            
            // Calcular subtotal
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.18; // IGV 18%
            $total = $subtotal + $tax;

            $quote = Quote::create([
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $request->discount ?? 0,
                'total' => $total - ($request->discount ?? 0),
                'status' => Quote::STATUS_DRAFT,
                'quote_date' => now(),
                'valid_until' => $request->valid_until,
                'terms_conditions' => $request->terms_conditions,
                'notes' => $request->notes,
                'metadata' => $request->metadata ?? []
            ]);

            // Crear items del presupuesto
            foreach ($request->items as $item) {
                $quote->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'discount' => $item['discount'] ?? 0
                ]);
            }

            DB::commit();

            return response()->json($quote->load(['customer', 'items.product']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el presupuesto: ' . $e->getMessage()], 500);
        }
    }

    public function show(Quote $quote)
    {
        return response()->json($quote->load(['customer', 'user', 'items.product']));
    }

    public function update(Request $request, Quote $quote)
    {
        // Solo se pueden modificar presupuestos en borrador
        if (!$quote->canBeModified()) {
            return response()->json([
                'message' => 'No se puede modificar un presupuesto que no está en borrador'
            ], 400);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Eliminar items existentes
            $quote->items()->delete();

            $subtotal = 0;
            
            // Recalcular con nuevos items
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.18;
            $total = $subtotal + $tax;

            $quote->update([
                'customer_id' => $request->customer_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $request->discount ?? 0,
                'total' => $total - ($request->discount ?? 0),
                'valid_until' => $request->valid_until,
                'terms_conditions' => $request->terms_conditions,
                'notes' => $request->notes
            ]);

            // Crear nuevos items
            foreach ($request->items as $item) {
                $quote->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'discount' => $item['discount'] ?? 0
                ]);
            }

            DB::commit();

            return response()->json($quote->load(['customer', 'items.product']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar el presupuesto: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Quote $quote)
    {
        // Solo se pueden eliminar presupuestos en borrador
        if (!$quote->canBeModified()) {
            return response()->json([
                'message' => 'No se puede eliminar un presupuesto que no está en borrador'
            ], 400);
        }

        $quote->delete();
        return response()->json(['message' => 'Presupuesto eliminado correctamente']);
    }

    // Acciones específicas de presupuestos
    public function send(Quote $quote)
    {
        if (!$quote->canBeSent()) {
            return response()->json([
                'message' => 'No se puede enviar este presupuesto'
            ], 400);
        }

        $quote->markAsSent();

        return response()->json([
            'message' => 'Presupuesto enviado correctamente',
            'quote' => $quote
        ]);
    }

    public function approve(Quote $quote)
    {
        if (!$quote->canBeApproved()) {
            return response()->json([
                'message' => 'No se puede aprobar este presupuesto'
            ], 400);
        }

        $quote->approve();

        return response()->json([
            'message' => 'Presupuesto aprobado correctamente',
            'quote' => $quote
        ]);
    }

    public function reject(Quote $quote)
    {
        if (!$quote->canBeApproved()) {
            return response()->json([
                'message' => 'No se puede rechazar este presupuesto'
            ], 400);
        }

        $quote->reject();

        return response()->json([
            'message' => 'Presupuesto rechazado',
            'quote' => $quote
        ]);
    }

    public function duplicate(Quote $quote)
    {
        DB::beginTransaction();
        try {
            $newQuote = Quote::create([
                'customer_id' => $quote->customer_id,
                'user_id' => auth()->id(),
                'subtotal' => $quote->subtotal,
                'tax' => $quote->tax,
                'discount' => $quote->discount,
                'total' => $quote->total,
                'status' => Quote::STATUS_DRAFT,
                'quote_date' => now(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'terms_conditions' => $quote->terms_conditions,
                'notes' => 'Duplicado de: ' . $quote->quote_number
            ]);

            // Duplicar items
            foreach ($quote->items as $item) {
                $newQuote->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'discount' => $item->discount
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Presupuesto duplicado correctamente',
                'quote' => $newQuote->load(['customer', 'items.product'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al duplicar presupuesto: ' . $e->getMessage()], 500);
        }
    }
}