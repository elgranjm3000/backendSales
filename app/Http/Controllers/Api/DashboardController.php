<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Ventas de hoy
        $todaySales = Sale::whereDate('sale_date', $today)
                         ->where('status', 'completed')
                         ->sum('total');
        
        // Ventas del mes
        $monthSales = Sale::where('sale_date', '>=', $thisMonth)
                         ->where('status', 'completed')
                         ->sum('total');
        
        // Productos con stock bajo
        $lowStockProducts = Product::lowStock()->count();
        
        // Total de clientes activos
        $totalCustomers = Customer::active()->count();
        
        // Ventas recientes
        $recentSales = Sale::with(['customer', 'items.product'])
                          ->where('status', 'completed')
                          ->orderBy('sale_date', 'desc')
                          ->limit(10)
                          ->get();
        
        // Top productos vendidos (este mes)
        $topProducts = Product::select('products.*')
                             ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
                             ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                             ->where('sales.sale_date', '>=', $thisMonth)
                             ->where('sales.status', 'completed')
                             ->groupBy('products.id')
                             ->orderByRaw('SUM(sale_items.quantity) DESC')
                             ->limit(5)
                             ->get();

        // Gráfico de ventas de los últimos 7 días
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sales = Sale::whereDate('sale_date', $date)
                        ->where('status', 'completed')
                        ->sum('total');
            
            $salesChart[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'total' => $sales
            ];
        }

        return response()->json([
            'metrics' => [
                'today_sales' => $todaySales,
                'month_sales' => $monthSales,
                'low_stock_products' => $lowStockProducts,
                'total_customers' => $totalCustomers
            ],
            'recent_sales' => $recentSales,
            'top_products' => $topProducts,
            'sales_chart' => $salesChart
        ]);
    }

    public function reports(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now()->endOfMonth();

        $salesByDay = Sale::selectRaw('DATE(sale_date) as date, SUM(total) as total, COUNT(*) as count')
                         ->whereBetween('sale_date', [$dateFrom, $dateTo])
                         ->where('status', 'completed')
                         ->groupBy('date')
                         ->orderBy('date')
                         ->get();

        $salesByPaymentMethod = Sale::selectRaw('payment_method, SUM(total) as total, COUNT(*) as count')
                                   ->whereBetween('sale_date', [$dateFrom, $dateTo])
                                   ->where('status', 'completed')
                                   ->groupBy('payment_method')
                                   ->get();

        $topCustomers = Customer::select('customers.*')
                               ->join('sales', 'customers.id', '=', 'sales.customer_id')
                               ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                               ->where('sales.status', 'completed')
                               ->groupBy('customers.id')
                               ->orderByRaw('SUM(sales.total) DESC')
                               ->limit(10)
                               ->get();

        return response()->json([
            'sales_by_day' => $salesByDay,
            'sales_by_payment_method' => $salesByPaymentMethod,
            'top_customers' => $topCustomers,
            'date_range' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d')
            ]
        ]);
    }
}