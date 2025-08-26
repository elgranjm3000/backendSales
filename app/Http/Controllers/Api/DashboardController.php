<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Seller;
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
        $activeCompanies = Company::where('status', 'active')->count();
        $activeSellers = Seller::where('seller_status', 'active')->count();

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
                    'active_companies' => $activeCompanies,
                    'active_sellers' => $activeSellers,
                    'monthly_revenue' => 45750.80,
                    'daily_transactions' => 127
                ],
                'user_distribution' => [
                    'admins' => User::where('role', 'admin')->count(),
                    'managers' => User::where('role', 'manager')->count(),
                    'companies' => User::where('role', 'company')->count(),
                    'sellers' => User::where('role', 'seller')->count()
                ],
                'recent_activities' => [
                    [
                        'id' => 1,
                        'type' => 'user_created',
                        'description' => 'Nuevo usuario creado: María González',
                        'timestamp' => now()->subMinutes(15)->format('Y-m-d H:i:s'),
                        'severity' => 'info'
                    ],
                    [
                        'id' => 2,
                        'type' => 'company_activated',
                        'description' => 'Compañía activada: Restaurant El Buen Sabor',
                        'timestamp' => now()->subHours(1)->format('Y-m-d H:i:s'),
                        'severity' => 'success'
                    ],
                    [
                        'id' => 3,
                        'type' => 'seller_commission',
                        'description' => 'Comisión procesada para vendedor: Juan Pérez ($125.50)',
                        'timestamp' => now()->subHours(3)->format('Y-m-d H:i:s'),
                        'severity' => 'info'
                    ],
                    [
                        'id' => 4,
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
                    'january' => ['revenue' => 38450.20, 'transactions' => 1250, 'new_users' => 15],
                    'february' => ['revenue' => 42130.75, 'transactions' => 1380, 'new_users' => 22],
                    'march' => ['revenue' => 45750.80, 'transactions' => 1520, 'new_users' => 18],
                ],
                'top_performing_companies' => [
                    ['name' => 'Restaurant El Buen Sabor', 'revenue' => 15250.30, 'growth' => '+12.5%'],
                    ['name' => 'Pizzería Italiana', 'revenue' => 12890.45, 'growth' => '+8.7%'],
                    ['name' => 'Café Central', 'revenue' => 9760.20, 'growth' => '+15.2%'],
                    ['name' => 'Marisquería Del Puerto', 'revenue' => 7849.85, 'growth' => '+6.3%']
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
        $activeCompanies = Company::where('status', 'active')->count();

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
                    'active_companies' => $activeCompanies,
                    'monthly_commission_pool' => 3245.75,
                    'pending_approvals' => 7
                ],
                'company_performance' => [
                    [
                        'company_name' => 'Restaurant El Buen Sabor',
                        'sellers_count' => 4,
                        'monthly_sales' => 15250.30,
                        'commission_paid' => 687.50,
                        'status' => 'excellent'
                    ],
                    [
                        'company_name' => 'Pizzería Italiana',
                        'sellers_count' => 2,
                        'monthly_sales' => 12890.45,
                        'commission_paid' => 520.15,
                        'status' => 'good'
                    ],
                    [
                        'company_name' => 'Café Central',
                        'sellers_count' => 3,
                        'monthly_sales' => 9760.20,
                        'commission_paid' => 425.80,
                        'status' => 'good'
                    ],
                    [
                        'company_name' => 'Marisquería Del Puerto',
                        'sellers_count' => 2,
                        'monthly_sales' => 7849.85,
                        'commission_paid' => 356.25,
                        'status' => 'average'
                    ]
                ],
                'pending_tasks' => [
                    [
                        'id' => 1,
                        'task' => 'Aprobar nuevo vendedor para Pizzería Italiana',
                        'priority' => 'high',
                        'due_date' => now()->addDays(1)->format('Y-m-d')
                    ],
                    [
                        'id' => 2,
                        'task' => 'Revisar comisiones pendientes del mes anterior',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(3)->format('Y-m-d')
                    ],
                    [
                        'id' => 3,
                        'task' => 'Actualizar términos de contrato con Restaurant El Buen Sabor',
                        'priority' => 'low',
                        'due_date' => now()->addWeek()->format('Y-m-d')
                    ]
                ],
                'seller_rankings' => [
                    ['name' => 'María González', 'company' => 'Restaurant El Buen Sabor', 'sales' => 4250.80, 'commission' => 191.25],
                    ['name' => 'Luis Torres', 'company' => 'Pizzería Italiana', 'sales' => 3890.45, 'commission' => 194.50],
                    ['name' => 'Patricia Vega', 'company' => 'Marisquería Del Puerto', 'sales' => 3654.20, 'commission' => 200.98],
                    ['name' => 'Carmen Silva', 'company' => 'Café Central', 'sales' => 3420.15, 'commission' => 143.65]
                ],
                'alerts' => [
                    [
                        'type' => 'warning',
                        'message' => 'Café Central - Sucursal Mall tiene bajo rendimiento este mes',
                        'timestamp' => now()->subHours(2)->format('Y-m-d H:i:s')
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
        $totalSellers = Seller::whereIn('company_id', $userCompanies->pluck('id'))->count();

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
                    'monthly_revenue' => 28650.95,
                    'monthly_growth' => '+14.2%',
                    'pending_orders' => 23,
                    'today_sales' => 1250.80
                ],
                'companies_overview' => $userCompanies->map(function($company) {
                    $sellersCount = Seller::where('company_id', $company->id)->count();
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'status' => $company->status,
                        'sellers_count' => $sellersCount,
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
                        'commission_earned' => 191.25,
                        'performance_rating' => 'excellent',
                        'status' => 'active'
                    ],
                    [
                        'seller_name' => 'Juan Pérez',
                        'company' => 'El Buen Sabor - Sucursal Norte',
                        'sales_today' => 315.50,
                        'sales_month' => 3180.75,
                        'commission_earned' => 120.87,
                        'performance_rating' => 'good',
                        'status' => 'active'
                    ],
                    [
                        'seller_name' => 'Fernando Castro',
                        'company' => 'Food Truck Delicias',
                        'sales_today' => 180.20,
                        'sales_month' => 2950.40,
                        'commission_earned' => 177.02,
                        'performance_rating' => 'good',
                        'status' => 'active'
                    ]
                ],
                'daily_sales_chart' => [
                    ['day' => 'Lunes', 'sales' => 950.30],
                    ['day' => 'Martes', 'sales' => 1120.45],
                    ['day' => 'Miércoles', 'sales' => 1350.80],
                    ['day' => 'Jueves', 'sales' => 980.25],
                    ['day' => 'Viernes', 'sales' => 1680.90],
                    ['day' => 'Sábado', 'sales' => 2150.75],
                    ['day' => 'Domingo', 'sales' => 1890.60]
                ],
                'popular_products' => [
                    ['name' => 'Seco de Cabrito', 'sales_count' => 45, 'revenue' => 832.50],
                    ['name' => 'Pizza Margherita', 'sales_count' => 38, 'revenue' => 532.00],
                    ['name' => 'Lomo Saltado', 'sales_count' => 35, 'revenue' => 560.00],
                    ['name' => 'Ceviche Mixto', 'sales_count' => 28, 'revenue' => 420.00],
                    ['name' => 'Café Americano', 'sales_count' => 67, 'revenue' => 201.00]
                ],
                'notifications' => [
                    [
                        'type' => 'success',
                        'message' => 'Venta record alcanzada en Restaurant El Buen Sabor',
                        'timestamp' => now()->subMinutes(30)->format('Y-m-d H:i:s')
                    ],
                    [
                        'type' => 'info',
                        'message' => 'Nuevo pedido de catering para evento corporativo',
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
                    'sales_today' => 485.30,
                    'sales_month' => 4250.80,
                    'commission_earned_month' => 191.25,
                    'commission_pending' => 45.80,
                    'performance_rating' => 'excellent',
                    'ranking_position' => 2
                ],
                'assigned_companies' => $sellerRecords->map(function($seller) {
                    return [
                        'company_id' => $seller->company->id,
                        'company_name' => $seller->company->name,
                        'seller_code' => $seller->code,
                        'status' => $seller->seller_status,
                        'commission_rate' => $seller->percent_sales . '%',
                        'is_inkeeper' => $seller->inkeeper,
                        'monthly_sales' => rand(1500, 5000) + (rand(0, 99) / 100),
                        'monthly_commission' => rand(50, 200) + (rand(0, 99) / 100)
                    ];
                }),
                'daily_performance' => [
                    ['hour' => '09:00', 'sales' => 45.50],
                    ['hour' => '10:00', 'sales' => 62.30],
                    ['hour' => '11:00', 'sales' => 78.90],
                    ['hour' => '12:00', 'sales' => 125.80],
                    ['hour' => '13:00', 'sales' => 98.45],
                    ['hour' => '14:00', 'sales' => 75.35],
                    ['hour' => '15:00', 'sales' => 0.00],
                    ['hour' => '16:00', 'sales' => 0.00]
                ],
                'sales_by_category' => [
                    ['category' => 'Platos Principales', 'sales' => 1250.80, 'percentage' => 35.2],
                    ['category' => 'Bebidas', 'sales' => 890.45, 'percentage' => 25.1],
                    ['category' => 'Postres', 'sales' => 456.30, 'percentage' => 12.8],
                    ['category' => 'Entradas', 'sales' => 653.25, 'percentage' => 18.4],
                    ['category' => 'Especiales', 'sales' => 295.50, 'percentage' => 8.5]
                ],
                'weekly_targets' => [
                    'weekly_goal' => 1500.00,
                    'current_progress' => 1250.80,
                    'percentage_completed' => 83.4,
                    'remaining_days' => 2,
                    'daily_average_needed' => 124.60
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
                    'Las ventas son 15% más altas los viernes por la noche',
                    'Los platos principales tienen mejor margen de comisión',
                    'Recuerda ofrecer postres para aumentar el ticket promedio',
                    'Tu mejor horario de ventas es entre 12:00-14:00'
                ]
            ]
        ]);
    }
}