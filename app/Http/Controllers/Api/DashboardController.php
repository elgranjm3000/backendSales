<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Seller;
use App\Models\Quote;
use App\Models\Customer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Obtener dashboard según el rol del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();

        switch ($user->role) {
            case User::ROLE_ADMIN:
                return $this->getAdminDashboard($user);
            case User::ROLE_MANAGER:
                return $this->getManagerDashboard($user);
            case User::ROLE_COMPANY:
                return $this->getCompanyDashboard($user);
            case User::ROLE_SELLER:
                return $this->getSellerDashboard($user);
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no autorizado para acceder al dashboard'
                ], 403);
        }
    }

    /**
     * Dashboard para Administrador
     */
    private function getAdminDashboard($user)
    {
        // Datos reales de la base de datos
        $totalUsers = User::count();
        $totalCompanies = Company::count();
        $totalSellers = Seller::count();
        $totalCustomers = Customer::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $activeSellers = Seller::where('seller_status', 'active')->count();
        
        // Métricas de presupuestos
        $quotesToday = Quote::whereDate('quote_date', today())->count();
        $quotesThisMonth = Quote::whereMonth('quote_date', now()->month)
                               ->whereYear('quote_date', now()->year)
                               ->count();
        $totalQuotes = Quote::count();

        return response()->json([
            'success' => true,
            'data' => [
                'user_info' => [
                    'name' => $user->name,
                    'role' => $user->role,
                    'last_login' => now()->subHours(2)->format('Y-m-d H:i:s')
                ],
                'summary_stats' => [
                    'total_users' => $totalUsers,
                    'total_companies' => $totalCompanies,
                    'total_sellers' => $totalSellers,
                    'total_customers' => $totalCustomers,
                    'active_companies' => $activeCompanies,
                    'active_sellers' => $activeSellers,
                    'total_quotes' => $totalQuotes,
                    'quotes_today' => $quotesToday,
                    'quotes_this_month' => $quotesThisMonth,
                    'monthly_revenue' => 45750.80,
                    'daily_transactions' => 127
                ],
                'quote_metrics' => [
                    'today' => [
                        'count' => $quotesToday,
                        'total_amount' => Quote::whereDate('quote_date', today())->sum('total'),
                        'average_amount' => $quotesToday > 0 ? Quote::whereDate('quote_date', today())->avg('total') : 0
                    ],
                    'this_month' => [
                        'count' => $quotesThisMonth,
                        'total_amount' => Quote::whereMonth('quote_date', now()->month)
                                              ->whereYear('quote_date', now()->year)
                                              ->sum('total'),
                        'average_amount' => $quotesThisMonth > 0 ? Quote::whereMonth('quote_date', now()->month)
                                                                       ->whereYear('quote_date', now()->year)
                                                                       ->avg('total') : 0
                    ],
                    'by_status' => [
                        'draft' => Quote::where('status', 'draft')->count(),
                        'sent' => Quote::where('status', 'sent')->count(),
                        'approved' => Quote::where('status', 'approved')->count(),
                        'rejected' => Quote::where('status', 'rejected')->count(),
                        'expired' => Quote::where('status', 'expired')->count()
                    ]
                ],
                'customer_metrics' => [
                    'total' => $totalCustomers,
                    'active' => Customer::where('status', 'active')->count(),
                    'new_this_month' => Customer::whereMonth('created_at', now()->month)
                                              ->whereYear('created_at', now()->year)
                                              ->count(),
                    'by_company' => Company::withCount('customers')->get()->map(function($company) {
                        return [
                            'company_name' => $company->name,
                            'customers_count' => $company->customers_count ?? 0
                        ];
                    })
                ],
                'user_distribution' => [
                    'admins' => User::where('role', 'admin')->count(),
                    'managers' => User::where('role', 'manager')->count(),
                    'companies' => User::where('role', 'company')->count(),
                    'sellers' => User::where('role', 'seller')->count()
                ],
                'recent_quotes' => Quote::with(['customer', 'company'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($quote) {
                        return [
                            'id' => $quote->id,
                            'number' => $quote->quote_number,
                            'customer' => $quote->customer->name ?? 'Cliente no disponible',
                            'company' => $quote->company->name ?? 'Compañía no disponible',
                            'total' => $quote->total,
                            'status' => $quote->status,
                            'status_label' => $this->getStatusLabel($quote->status),
                            'date' => $quote->quote_date->format('Y-m-d H:i:s'),
                            'valid_until' => $quote->valid_until,
                            'days_remaining' => $quote->valid_until ? now()->diffInDays($quote->valid_until, false) : null,
                            'created_at' => $quote->created_at->format('Y-m-d H:i:s'),
                            'items_count' => $quote->items()->count()
                        ];
                    }),
                'recent_activities' => [
                    [
                        'id' => 1,
                        'type' => 'quote_created',
                        'description' => 'Nueva cotización creada: COT-2025-005 por $1,250.80',
                        'timestamp' => now()->subMinutes(15)->format('Y-m-d H:i:s'),
                        'severity' => 'info'
                    ],
                    [
                        'id' => 2,
                        'type' => 'customer_created',
                        'description' => 'Nuevo cliente registrado: María González',
                        'timestamp' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
                        'severity' => 'success'
                    ],
                    [
                        'id' => 3,
                        'type' => 'company_activated',
                        'description' => 'Compañía activada: Restaurant El Buen Sabor',
                        'timestamp' => now()->subHours(1)->format('Y-m-d H:i:s'),
                        'severity' => 'success'
                    ],
                    [
                        'id' => 4,
                        'type' => 'seller_commission',
                        'description' => 'Comisión procesada para vendedor: Juan Pérez ($125.50)',
                        'timestamp' => now()->subHours(3)->format('Y-m-d H:i:s'),
                        'severity' => 'info'
                    ],
                    [
                        'id' => 5,
                        'type' => 'system_backup',
                        'description' => 'Backup automático del sistema completado',
                        'timestamp' => now()->subHours(6)->format('Y-m-d H:i:s'),
                        'severity' => 'success'
                    ]
                ],
                'performance_metrics' => [
                    'system_health' => 98.5,
                    'api_response_time' => '145ms',
                    'database_connections' => 23,
                    'active_sessions' => 56
                ],
                'monthly_trends' => [
                    'january' => ['revenue' => 38450.20, 'transactions' => 1250, 'new_users' => 15, 'quotes' => 87],
                    'february' => ['revenue' => 42130.75, 'transactions' => 1380, 'new_users' => 22, 'quotes' => 95],
                    'march' => ['revenue' => 45750.80, 'transactions' => 1520, 'new_users' => 18, 'quotes' => 103],
                ],
                'top_performing_companies' => [
                    ['name' => 'Restaurant El Buen Sabor', 'revenue' => 15250.30, 'growth' => '+12.5%', 'quotes_count' => 45],
                    ['name' => 'Pizzería Italiana', 'revenue' => 12890.45, 'growth' => '+8.7%', 'quotes_count' => 32],
                    ['name' => 'Café Central', 'revenue' => 9760.20, 'growth' => '+15.2%', 'quotes_count' => 28],
                    ['name' => 'Marisquería Del Puerto', 'revenue' => 7849.85, 'growth' => '+6.3%', 'quotes_count' => 18]
                ]
            ]
        ]);
    }

    /**
     * Dashboard para Manager
     */
    private function getManagerDashboard($user)
    {
        $totalCompanies = Company::count();
        $totalSellers = Seller::count();
        $totalCustomers = Customer::count();
        $activeCompanies = Company::where('status', 'active')->count();
        
        // Métricas de presupuestos para manager
        $quotesToday = Quote::whereDate('quote_date', today())->count();
        $quotesThisMonth = Quote::whereMonth('quote_date', now()->month)
                               ->whereYear('quote_date', now()->year)
                               ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user_info' => [
                    'name' => $user->name,
                    'role' => $user->role,
                    'last_login' => now()->subMinutes(30)->format('Y-m-d H:i:s')
                ],
                'summary_stats' => [
                    'managed_companies' => $totalCompanies,
                    'total_sellers' => $totalSellers,
                    'total_customers' => $totalCustomers,
                    'active_companies' => $activeCompanies,
                    'quotes_today' => $quotesToday,
                    'quotes_this_month' => $quotesThisMonth,
                    'monthly_commission_pool' => 3245.75,
                    'pending_approvals' => 7
                ],
                'quote_overview' => [
                    'today_count' => $quotesToday,
                    'today_amount' => Quote::whereDate('quote_date', today())->sum('total'),
                    'month_count' => $quotesThisMonth,
                    'month_amount' => Quote::whereMonth('quote_date', now()->month)
                                          ->whereYear('quote_date', now()->year)
                                          ->sum('total'),
                    'pending_approval' => Quote::where('status', 'sent')->count(),
                    'approved_this_month' => Quote::where('status', 'approved')
                                                 ->whereMonth('approved_at', now()->month)
                                                 ->whereYear('approved_at', now()->year)
                                                 ->count()
                ],
                'company_performance' => [
                    [
                        'company_name' => 'Restaurant El Buen Sabor',
                        'sellers_count' => 4,
                        'customers_count' => 12,
                        'monthly_sales' => 15250.30,
                        'quotes_count' => 8,
                        'commission_paid' => 687.50,
                        'status' => 'excellent'
                    ],
                    [
                        'company_name' => 'Pizzería Italiana',
                        'sellers_count' => 2,
                        'customers_count' => 8,
                        'monthly_sales' => 12890.45,
                        'quotes_count' => 6,
                        'commission_paid' => 520.15,
                        'status' => 'good'
                    ],
                    [
                        'company_name' => 'Café Central',
                        'sellers_count' => 3,
                        'customers_count' => 15,
                        'monthly_sales' => 9760.20,
                        'quotes_count' => 4,
                        'commission_paid' => 425.80,
                        'status' => 'good'
                    ],
                    [
                        'company_name' => 'Marisquería Del Puerto',
                        'sellers_count' => 2,
                        'customers_count' => 6,
                        'monthly_sales' => 7849.85,
                        'quotes_count' => 3,
                        'commission_paid' => 356.25,
                        'status' => 'average'
                    ]
                ],
                'pending_tasks' => [
                    [
                        'id' => 1,
                        'task' => 'Aprobar cotización COT-2025-008 - Restaurant El Buen Sabor ($2,350.00)',
                        'priority' => 'high',
                        'due_date' => now()->addDays(1)->format('Y-m-d')
                    ],
                    [
                        'id' => 2,
                        'task' => 'Revisar nuevos clientes registrados esta semana (5 pendientes)',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(2)->format('Y-m-d')
                    ],
                    [
                        'id' => 3,
                        'task' => 'Revisar comisiones pendientes del mes anterior',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(3)->format('Y-m-d')
                    ],
                    [
                        'id' => 4,
                        'task' => 'Actualizar términos de contrato con Restaurant El Buen Sabor',
                        'priority' => 'low',
                        'due_date' => now()->addWeek()->format('Y-m-d')
                    ]
                ],
                'seller_rankings' => [
                    ['name' => 'María González', 'company' => 'Restaurant El Buen Sabor', 'sales' => 4250.80, 'quotes_created' => 12, 'commission' => 191.25],
                    ['name' => 'Luis Torres', 'company' => 'Pizzería Italiana', 'sales' => 3890.45, 'quotes_created' => 10, 'commission' => 194.50],
                    ['name' => 'Patricia Vega', 'company' => 'Marisquería Del Puerto', 'sales' => 3654.20, 'quotes_created' => 8, 'commission' => 200.98],
                    ['name' => 'Carmen Silva', 'company' => 'Café Central', 'sales' => 3420.15, 'quotes_created' => 7, 'commission' => 143.65]
                ],
                'alerts' => [
                    [
                        'type' => 'info',
                        'message' => '3 cotizaciones pendientes de aprobación desde hace más de 48 horas',
                        'timestamp' => now()->subHours(1)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'warning',
                        'message' => 'Café Central - Sucursal Mall tiene bajo rendimiento este mes',
                        'timestamp' => now()->subHours(2)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'info',
                        'message' => '5 nuevos clientes registrados esta semana',
                        'timestamp' => now()->subHours(3)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'info',
                        'message' => '3 vendedores necesitan renovar certificaciones',
                        'timestamp' => now()->subHours(5)->format('Y-m-d H:i:s')
                    ]
                ]
            ]
        ]);
    }

    /**
     * Dashboard para Company
     */
    private function getCompanyDashboard($user)
    {
        $userCompanies = Company::where('user_id', $user->id)->get();
        $companyIds = $userCompanies->pluck('id');
        
        $totalSellers = Seller::whereIn('company_id', $companyIds)->count();
        $totalCustomers = Customer::whereIn('company_id', $companyIds)->count();
        
        // Métricas de presupuestos para la compañía
        $quotesToday = Quote::whereIn('company_id', $companyIds)
                           ->whereDate('quote_date', today())
                           ->count();
        $quotesThisMonth = Quote::whereIn('company_id', $companyIds)
                               ->whereMonth('quote_date', now()->month)
                               ->whereYear('quote_date', now()->year)
                               ->count();
        $totalQuotes = Quote::whereIn('company_id', $companyIds)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user_info' => [
                    'name' => $user->name,
                    'role' => $user->role,
                    'last_login' => now()->subMinutes(10)->format('Y-m-d H:i:s')
                ],
                'business_summary' => [
                    'total_companies' => $userCompanies->count(),
                    'total_sellers' => $totalSellers,
                    'total_customers' => $totalCustomers,
                    'total_quotes' => $totalQuotes,
                    'quotes_today' => $quotesToday,
                    'quotes_this_month' => $quotesThisMonth,
                    'monthly_revenue' => 28650.95,
                    'monthly_growth' => '+14.2%',
                    'pending_orders' => 23,
                    'today_sales' => 1250.80
                ],
                 'recent_quotes' => Quote::with(['customer', 'company'])
                    ->whereIn('company_id', $companyIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(8)
                    ->get()
                    ->map(function($quote) {
                        return [
                            'id' => $quote->id,
                            'number' => $quote->quote_number,
                            'customer' => $quote->customer->name ?? 'Cliente no disponible',
                            'company' => $quote->company->name ?? 'Compañía no disponible',
                            'total' => $quote->total,
                            'status' => $quote->status,
                            'status_label' => $this->getStatusLabel($quote->status),
                            'date' => $quote->quote_date->format('Y-m-d H:i:s'),
                            'valid_until' => $quote->valid_until,
                            'days_remaining' => $quote->valid_until ? now()->diffInDays($quote->valid_until, false) : null,
                            'created_at' => $quote->created_at->format('Y-m-d H:i:s'),
                            'items_count' => $quote->items()->count(),
                            'urgency' => $this->getQuoteUrgency($quote)
                        ];
                    }),
                'quote_metrics' => [
                    'today' => [
                        'count' => $quotesToday,
                        'total_amount' => Quote::whereIn('company_id', $companyIds)
                                              ->whereDate('quote_date', today())
                                              ->sum('total'),
                        'pending_count' => Quote::whereIn('company_id', $companyIds)
                                               ->where('status', 'draft')
                                               ->count()
                    ],
                    'this_month' => [
                        'count' => $quotesThisMonth,
                        'total_amount' => Quote::whereIn('company_id', $companyIds)
                                              ->whereMonth('quote_date', now()->month)
                                              ->whereYear('quote_date', now()->year)
                                              ->sum('total'),
                        'approved_count' => Quote::whereIn('company_id', $companyIds)
                                                ->where('status', 'approved')
                                                ->whereMonth('approved_at', now()->month)
                                                ->whereYear('approved_at', now()->year)
                                                ->count()
                    ],
                    'by_status' => [
                        'draft' => Quote::whereIn('company_id', $companyIds)->where('status', 'draft')->count(),
                        'sent' => Quote::whereIn('company_id', $companyIds)->where('status', 'sent')->count(),
                        'approved' => Quote::whereIn('company_id', $companyIds)->where('status', 'approved')->count(),
                        'rejected' => Quote::whereIn('company_id', $companyIds)->where('status', 'rejected')->count(),
                        'expired' => Quote::whereIn('company_id', $companyIds)->where('status', 'expired')->count()
                    ]
                ],
                'customer_overview' => [
                    'total' => $totalCustomers,
                    'active' => Customer::whereIn('company_id', $companyIds)->where('status', 'active')->count(),
                    'new_this_month' => Customer::whereIn('company_id', $companyIds)
                                              ->whereMonth('created_at', now()->month)
                                              ->whereYear('created_at', now()->year)
                                              ->count()
                ],
                'companies_overview' => $userCompanies->map(function($company) {
                    $sellersCount = Seller::where('company_id', $company->id)->count();
                    $customersCount = Customer::where('company_id', $company->id)->count();
                    $quotesCount = Quote::where('company_id', $company->id)->count();
                    
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'status' => $company->status,
                        'sellers_count' => $sellersCount,
                        'customers_count' => $customersCount,
                        'quotes_count' => $quotesCount,
                        'monthly_sales' => rand(5000, 20000) + (rand(0, 99) / 100),
                        'daily_sales' => rand(200, 800) + (rand(0, 99) / 100),
                        'commission_rate' => '4.5%'
                    ];
                }),
                'seller_performance' => [
                    [
                        'seller_name' => 'María González',
                        'company' => 'Restaurant El Buen Sabor',
                        'sales_today' => 420.80,
                        'sales_month' => 4250.30,
                        'quotes_created' => 8,
                        'customers_served' => 15,
                        'commission_earned' => 191.25,
                        'performance_rating' => 'excellent',
                        'status' => 'active'
                    ],
                    [
                        'seller_name' => 'Juan Pérez',
                        'company' => 'El Buen Sabor - Sucursal Norte',
                        'sales_today' => 315.50,
                        'sales_month' => 3180.75,
                        'quotes_created' => 6,
                        'customers_served' => 12,
                        'commission_earned' => 120.87,
                        'performance_rating' => 'good',
                        'status' => 'active'
                    ],
                    [
                        'seller_name' => 'Fernando Castro',
                        'company' => 'Food Truck Delicias',
                        'sales_today' => 180.20,
                        'sales_month' => 2950.40,
                        'quotes_created' => 4,
                        'customers_served' => 8,
                        'commission_earned' => 177.02,
                        'performance_rating' => 'good',
                        'status' => 'active'
                    ]
                ],
                'daily_sales_chart' => [
                    ['day' => 'Lunes', 'sales' => 950.30, 'quotes' => 3],
                    ['day' => 'Martes', 'sales' => 1120.45, 'quotes' => 4],
                    ['day' => 'Miércoles', 'sales' => 1350.80, 'quotes' => 6],
                    ['day' => 'Jueves', 'sales' => 980.25, 'quotes' => 2],
                    ['day' => 'Viernes', 'sales' => 1680.90, 'quotes' => 8],
                    ['day' => 'Sábado', 'sales' => 2150.75, 'quotes' => 5],
                    ['day' => 'Domingo', 'sales' => 1890.60, 'quotes' => 3]
                ],
                'popular_products' => [
                    ['name' => 'Seco de Cabrito', 'sales_count' => 45, 'revenue' => 832.50, 'quotes_featured' => 12],
                    ['name' => 'Pizza Margherita', 'sales_count' => 38, 'revenue' => 532.00, 'quotes_featured' => 8],
                    ['name' => 'Lomo Saltado', 'sales_count' => 35, 'revenue' => 560.00, 'quotes_featured' => 10],
                    ['name' => 'Ceviche Mixto', 'sales_count' => 28, 'revenue' => 420.00, 'quotes_featured' => 6],
                    ['name' => 'Café Americano', 'sales_count' => 67, 'revenue' => 201.00, 'quotes_featured' => 15]
                ],
                'notifications' => [
                    [
                        'type' => 'success',
                        'message' => 'Nueva cotización aprobada por $2,450.00 - Cliente: Empresa ABC',
                        'timestamp' => now()->subMinutes(15)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'info',
                        'message' => '3 cotizaciones creadas hoy por un total de $5,680.50',
                        'timestamp' => now()->subMinutes(30)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'success',
                        'message' => 'Venta record alcanzada en Restaurant El Buen Sabor',
                        'timestamp' => now()->subMinutes(45)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'info',
                        'message' => 'Nuevo cliente registrado: Carlos Mendoza',
                        'timestamp' => now()->subHours(1)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'warning',
                        'message' => 'Stock bajo en ingredientes para pizzas - Food Truck',
                        'timestamp' => now()->subHours(2)->format('Y-m-d H:i:s')
                    ]
                ]
            ]
        ]);
    }

    /**
     * Dashboard para Seller
     */
    private function getSellerDashboard($user)
    {
        $sellerRecords = Seller::where('user_id', $user->id)->with('company')->get();
        $companyIds = $sellerRecords->pluck('company_id');
        
        // Métricas de presupuestos para el vendedor
        $quotesToday = Quote::whereIn('company_id', $companyIds)
                           ->whereDate('quote_date', today())
                           ->count();
        $quotesThisMonth = Quote::whereIn('company_id', $companyIds)
                               ->whereMonth('quote_date', now()->month)
                               ->whereYear('quote_date', now()->year)
                               ->count();
        $customersCount = Customer::whereIn('company_id', $companyIds)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user_info' => [
                    'name' => $user->name,
                    'role' => $user->role,
                    'last_login' => now()->subMinutes(5)->format('Y-m-d H:i:s')
                ],
                'seller_summary' => [
                    'companies_count' => $sellerRecords->count(),
                    'customers_count' => $customersCount,
                    'quotes_today' => $quotesToday,
                    'quotes_this_month' => $quotesThisMonth,
                    'sales_today' => 485.30,
                    'sales_month' => 4250.80,
                    'commission_earned_month' => 191.25,
                    'commission_pending' => 45.80,
                    'performance_rating' => 'excellent',
                    'ranking_position' => 2
                ],
                'quote_performance' => [
                    'today' => [
                        'count' => $quotesToday,
                        'total_amount' => Quote::whereIn('company_id', $companyIds)
                                              ->whereDate('quote_date', today())
                                              ->sum('total'),
                        'approved' => Quote::whereIn('company_id', $companyIds)
                                          ->where('status', 'approved')
                                          ->whereDate('approved_at', today())
                                          ->count()
                    ],
                    'this_month' => [
                        'count' => $quotesThisMonth,
                        'total_amount' => Quote::whereIn('company_id', $companyIds)
                                              ->whereMonth('quote_date', now()->month)
                                              ->whereYear('quote_date', now()->year)
                                              ->sum('total'),
                        'conversion_rate' => $quotesThisMonth > 0 ? 
                            round((Quote::whereIn('company_id', $companyIds)
                                       ->where('status', 'approved')
                                       ->whereMonth('approved_at', now()->month)
                                       ->whereYear('approved_at', now()->year)
                                       ->count() / $quotesThisMonth) * 100, 1) : 0
                    ]
                ],
                'customer_metrics' => [
                    'total_served' => $customersCount,
                    'new_this_month' => Customer::whereIn('company_id', $companyIds)
                                              ->whereMonth('created_at', now()->month)
                                              ->whereYear('created_at', now()->year)
                                              ->count(),
                    'active_customers' => Customer::whereIn('company_id', $companyIds)
                                                ->where('status', 'active')
                                                ->count()
                ],
                'assigned_companies' => $sellerRecords->map(function($seller) {
                    $companyQuotes = Quote::where('company_id', $seller->company->id)->count();
                    $companyCustomers = Customer::where('company_id', $seller->company->id)->count();
                    
                    return [
                        'company_id' => $seller->company->id,
                        'company_name' => $seller->company->name,
                        'seller_code' => $seller->code,
                        'status' => $seller->seller_status,
                        'commission_rate' => $seller->percent_sales . '%',
                        'is_inkeeper' => $seller->inkeeper,
                        'monthly_sales' => rand(1500, 5000) + (rand(0, 99) / 100),
                        'monthly_commission' => rand(50, 200) + (rand(0, 99) / 100),
                        'quotes_created' => $companyQuotes,
                        'customers_served' => $companyCustomers
                    ];
                }),
                'daily_performance' => [
                    ['hour' => '09:00', 'sales' => 45.50, 'quotes' => 1],
                    ['hour' => '10:00', 'sales' => 62.30, 'quotes' => 0],
                    ['hour' => '11:00', 'sales' => 78.90, 'quotes' => 2],
                    ['hour' => '12:00', 'sales' => 125.80, 'quotes' => 3],
                    ['hour' => '13:00', 'sales' => 98.45, 'quotes' => 1],
                    ['hour' => '14:00', 'sales' => 75.35, 'quotes' => 1],
                    ['hour' => '15:00', 'sales' => 0.00, 'quotes' => 0],
                    ['hour' => '16:00', 'sales' => 0.00, 'quotes' => 0]
                ],
                'sales_by_category' => [
                    ['category' => 'Platos Principales', 'sales' => 1250.80, 'percentage' => 35.2, 'quotes_count' => 15],
                    ['category' => 'Bebidas', 'sales' => 890.45, 'percentage' => 25.1, 'quotes_count' => 22],
                    ['category' => 'Postres', 'sales' => 456.30, 'percentage' => 12.8, 'quotes_count' => 8],
                    ['category' => 'Entradas', 'sales' => 653.25, 'percentage' => 18.4, 'quotes_count' => 12],
                    ['category' => 'Especiales', 'sales' => 295.50, 'percentage' => 8.5, 'quotes_count' => 5]
                ],
                'weekly_targets' => [
                    'weekly_goal' => 1500.00,
                    'current_progress' => 1250.80,
                    'percentage_completed' => 83.4,
                    'remaining_days' => 2,
                    'daily_average_needed' => 124.60,
                    'quotes_goal' => 8,
                    'quotes_completed' => 6
                ],
                'recent_quotes' => [
                    [
                        'quote_number' => 'COT-2025-012',
                        'customer' => 'María Rodríguez',
                        'company' => 'Restaurant El Buen Sabor',
                        'total' => 125.50,
                        'status' => 'sent',
                        'items_count' => 4,
                        'created_at' => now()->subMinutes(30)->format('Y-m-d H:i:s')
                    ],
                    [
                        'quote_number' => 'COT-2025-011',
                        'customer' => 'Empresa Tech Solutions',
                        'company' => 'Restaurant El Buen Sabor',
                        'total' => 890.00,
                        'status' => 'approved',
                        'items_count' => 8,
                        'created_at' => now()->subHours(2)->format('Y-m-d H:i:s')
                    ],
                    [
                        'quote_number' => 'COT-2025-010',
                        'customer' => 'Carlos Mendoza',
                        'company' => 'Café Central',
                        'total' => 67.80,
                        'status' => 'draft',
                        'items_count' => 3,
                        'created_at' => now()->subHours(4)->format('Y-m-d H:i:s')
                    ]
                ],
                'recent_sales' => [
                    [
                        'order_id' => 'ORD-2024-001',
                        'customer' => 'Mesa 5',
                        'items' => ['Seco de Cabrito', 'Chicha Morada'],
                        'total' => 22.50,
                        'commission' => 1.01,
                        'timestamp' => now()->subMinutes(15)->format('Y-m-d H:i:s')
                    ],
                    [
                        'order_id' => 'ORD-2024-002',
                        'customer' => 'Mesa 12',
                        'items' => ['Pizza Margherita', 'Coca Cola'],
                        'total' => 17.50,
                        'commission' => 0.79,
                        'timestamp' => now()->subMinutes(45)->format('Y-m-d H:i:s')
                    ],
                    [
                        'order_id' => 'ORD-2024-003',
                        'customer' => 'Delivery - Juan P.',
                        'items' => ['Lomo Saltado', 'Inca Kola', 'Postre del día'],
                        'total' => 25.80,
                        'commission' => 1.16,
                        'timestamp' => now()->subHours(1)->format('Y-m-d H:i:s')
                    ]
                ],
                'achievements' => [
                    [
                        'title' => 'Vendedor del Mes',
                        'description' => 'Mayor volumen de ventas en marzo',
                        'earned_date' => '2024-03-31',
                        'badge_color' => 'gold'
                    ],
                    [
                        'title' => 'Maestro de Cotizaciones',
                        'description' => '90% de tasa de conversión en cotizaciones',
                        'earned_date' => '2024-03-15',
                        'badge_color' => 'purple'
                    ],
                    [
                        'title' => 'Cliente Satisfecho',
                        'description' => '50+ reseñas positivas',
                        'earned_date' => '2024-02-15',
                        'badge_color' => 'blue'
                    ],
                    [
                        'title' => 'Meta Superada',
                        'description' => 'Superó la meta mensual por 3 meses consecutivos',
                        'earned_date' => '2024-01-31',
                        'badge_color' => 'green'
                    ]
                ],
                'tips_and_insights' => [
                    'Las cotizaciones enviadas los martes tienen 25% más posibilidades de aprobación',
                    'Tus clientes corporativos prefieren cotizaciones detalladas con términos claros',
                    'Las ventas son 15% más altas los viernes por la noche',
                    'Los platos principales tienen mejor margen de comisión',
                    'Recuerda hacer seguimiento a cotizaciones después de 48 horas de enviadas',
                    'Tu mejor horario de ventas es entre 12:00-14:00'
                ]
            ]
        ]);
    }

      private function getStatusLabel($status)
    {
        $labels = [
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'expired' => 'Expirada'
        ];

        return $labels[$status] ?? 'Desconocido';
    }

     private function getQuoteUrgency($quote)
    {
        if (!$quote->valid_until) {
            return 'none';
        }

        $daysRemaining = now()->diffInDays($quote->valid_until, false);
        
        if ($daysRemaining < 0) {
            return 'expired';
        } elseif ($daysRemaining <= 2) {
            return 'high';
        } elseif ($daysRemaining <= 7) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}