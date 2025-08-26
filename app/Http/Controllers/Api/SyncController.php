<?php
// app/Http/Controllers/Api/SyncController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Quote;
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

    public function syncQuotes(Request $request)
    {
        $request->validate([
            'quotes' => 'required|array',
            'quotes.*.offline_id' => 'required|string',
            'quotes.*.customer_id' => 'required|exists:customers,id',
            'quotes.*.items' => 'required|array',
            'quotes.*.status' => 'nullable|in:draft,sent,approved,rejected',
            'quotes.*.valid_until' => 'nullable|date'
        ]);

        $syncedQuotes = [];
        
        foreach ($request->quotes as $quoteData) {
            try {
                $quote = Quote::create([
                    'customer_id' => $quoteData['customer_id'],
                    'user_id' => auth()->id(),
                    'subtotal' => $quoteData['subtotal'],
                    'tax' => $quoteData['tax'],
                    'total' => $quoteData['total'],
                    'status' => $quoteData['status'] ?? Quote::STATUS_DRAFT,
                    'quote_date' => $quoteData['quote_date'] ?? now(),
                    'valid_until' => $quoteData['valid_until'] ?? now()->addDays(30)->toDateString(),
                    'terms_conditions' => $quoteData['terms_conditions'] ?? null,
                    'notes' => $quoteData['notes'] ?? null,
                    'metadata' => ['offline_id' => $quoteData['offline_id']]
                ]);

                foreach ($quoteData['items'] as $item) {
                    $quote->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'discount' => $item['discount'] ?? 0
                    ]);
                }

                $syncedQuotes[] = [
                    'offline_id' => $quoteData['offline_id'],
                    'server_id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'status' => 'success'
                ];

            } catch (\Exception $e) {
                $syncedQuotes[] = [
                    'offline_id' => $quoteData['offline_id'],
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        SyncLog::create([
            'user_id' => auth()->id(),
            'entity_type' => 'quotes',
            'action' => 'sync',
            'data' => [
                'total_quotes' => count($request->quotes),
                'successful' => count(array_filter($syncedQuotes, fn($q) => $q['status'] === 'success')),
                'failed' => count(array_filter($syncedQuotes, fn($q) => $q['status'] === 'error'))
            ],
            'synced_at' => now()
        ]);

        return response()->json([
            'synced_quotes' => $syncedQuotes,
            'sync_time' => now()->toISOString(),
            'summary' => [
                'total' => count($syncedQuotes),
                'successful' => count(array_filter($syncedQuotes, fn($q) => $q['status'] === 'success')),
                'failed' => count(array_filter($syncedQuotes, fn($q) => $q['status'] === 'error'))
            ]
        ]);
    }

    public function getQuotes(Request $request)
    {
        $lastSync = $request->header('Last-Sync');
        $query = Quote::with(['customer', 'items.product']);
        
        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }
        
        // Solo sincronizar presupuestos del usuario autenticado o todos si es admin
        $user = auth()->user();
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        
        $quotes = $query->orderBy('quote_date', 'desc')->get();
        
        SyncLog::create([
            'user_id' => auth()->id(),
            'entity_type' => 'quotes',
            'action' => 'download',
            'data' => ['count' => $quotes->count()],
            'synced_at' => now()
        ]);

        return response()->json([
            'quotes' => $quotes,
            'sync_time' => now()->toISOString()
        ]);
    }
}