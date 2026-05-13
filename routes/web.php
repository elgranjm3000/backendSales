<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Web\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// API Routes (mantenidas para compatibilidad)
// =========================================================================

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
    Route::get('reports/dashboard', [DashboardController::class, 'index']);
    Route::get('reports', [DashboardController::class, 'reports']);

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

    Route::post('categories', function (\Illuminate\Http\Request $request) {
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

// =========================================================================
// Frontend Web Routes
// =========================================================================

// Login (público)
Route::get('/', [AdminController::class, 'loginForm'])->name('home');
Route::get('/login', [AdminController::class, 'loginForm'])->name('login');
Route::post('/login', [AdminController::class, 'login']);

// Panel de administración (solo cajeros)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/accesos', [AdminController::class, 'index'])->name('admin.accesos');
    Route::post('/accesos', [AdminController::class, 'store'])->name('admin.accesos.store');
    Route::post('/accesos/{id}/toggle-block', [AdminController::class, 'toggleBlock'])->name('admin.accesos.toggle-block');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
