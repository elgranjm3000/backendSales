<?php
// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Dashboard y reportes
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('reports/dashboard', [DashboardController::class, 'index']); // Alias
    Route::get('reports', [DashboardController::class, 'reports']);

    // Sincronización offline
    Route::prefix('sync')->group(function () {
        Route::get('products', [SyncController::class, 'syncProducts']);
        Route::get('customers', [SyncController::class, 'syncCustomers']);
        Route::post('sales', [SyncController::class, 'syncSales']);
    });

    // CRUD Productos
    Route::apiResource('products', ProductController::class);

    // CRUD Clientes
    Route::apiResource('customers', CustomerController::class);

    // CRUD Ventas
    Route::apiResource('sales', SaleController::class);

    // Rutas adicionales para categorías
    Route::get('categories', function () {
        return response()->json(\App\Models\Category::active()->get());
    });

    Route::post('categories', function (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $category = \App\Models\Category::create($request->all());
        return response()->json($category, 201);
    });
});

// Ruta de verificación de salud de la API
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});