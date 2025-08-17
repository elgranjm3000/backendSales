<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'items.product']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $sales = $query->orderBy('sale_date', 'desc')
                      ->paginate($request->per_page ?? 20);

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer,credit',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            
            // Calcular subtotal y verificar stock
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'message' => "Stock insuficiente para el producto: {$product->name}"
                    ], 400);
                }
                
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.18; // IGV 18%
            $total = $subtotal + $tax;

            $sale = Sale::create([
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $request->discount ?? 0,
                'total' => $total - ($request->discount ?? 0),
                'payment_method' => $request->payment_method,
                'payment_status' => 'paid',
                'status' => 'completed',
                'sale_date' => now(),
                'notes' => $request->notes,
                'metadata' => $request->metadata ?? []
            ]);

            // Crear items de venta y actualizar stock
            foreach ($request->items as $item) {
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'discount' => $item['discount'] ?? 0
                ]);

                // Actualizar stock
                Product::where('id', $item['product_id'])
                       ->decrement('stock', $item['quantity']);
            }

            DB::commit();

            return response()->json($sale->load(['customer', 'items.product']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear la venta: ' . $e->getMessage()], 500);
        }
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['customer', 'user', 'items.product']));
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,partial'
        ]);

        $sale->update($request->only(['status', 'payment_status', 'notes']));

        return response()->json($sale);
    }
}