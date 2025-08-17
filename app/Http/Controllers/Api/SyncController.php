<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SyncController extends Controller
{
    public function syncProducts(Request $request)
    {
        $lastSync = $request->header('Last-Sync');
        $query = Product::with('category')->active();
        
        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }
        
        $products = $query->get();
        
        SyncLog::create([
            'user_id' => auth()->id(),
            'entity_type' => 'products',
            'action' => 'sync',
            'data' => ['count' => $products->count()],
            'synced_at' => now()
        ]);

        return response()->json([
            'products' => $products,
            'sync_time' => now()->toISOString()
        ]);
    }

    public function syncCustomers(Request $request)
    {
        $lastSync = $request->header('Last-Sync');
        $query = Customer::active();
        
        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }
        
        $customers = $query->get();
        
        SyncLog::create([
            'user_id' => auth()->id(),
            'entity_type' => 'customers',
            'action' => 'sync',
            'data' => ['count' => $customers->count()],
            'synced_at' => now()
        ]);

        return response()->json([
            'customers' => $customers,
            'sync_time' => now()->toISOString()
        ]);
    }

    public function syncSales(Request $request)
    {
        $request->validate([
            'sales' => 'required|array',
            'sales.*.offline_id' => 'required|string',
            'sales.*.customer_id' => 'required|exists:customers,id',
            'sales.*.items' => 'required|array'
        ]);

        $syncedSales = [];
        
        foreach ($request->sales as $saleData) {
            try {
                $sale = Sale::create([
                    'customer_id' => $saleData['customer_id'],
                    'user_id' => auth()->id(),
                    'subtotal' => $saleData['subtotal'],
                    'tax' => $saleData['tax'],
                    'total' => $saleData['total'],
                    'payment_method' => $saleData['payment_method'] ?? 'cash',
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'sale_date' => $saleData['sale_date'] ?? now(),
                    'notes' => $saleData['notes'] ?? null,
                    'metadata' => ['offline_id' => $saleData['offline_id']]
                ]);

                foreach ($saleData['items'] as $item) {
                    $sale->items()->create($item);
                }

                $syncedSales[] = [
                    'offline_id' => $saleData['offline_id'],
                    'server_id' => $sale->id,
                    'sale_number' => $sale->sale_number
                ];

            } catch (\Exception $e) {
                $syncedSales[] = [
                    'offline_id' => $saleData['offline_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'synced_sales' => $syncedSales,
            'sync_time' => now()->toISOString()
        ]);
    }
}