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
Route::get('/login/refresh-csrf', [AdminController::class, 'refreshCsrfToken'])->name('login.refresh-csrf');

// Panel de administración (admin y manager pueden ver accesos)
Route::middleware(['auth', 'admin.or.manager'])->prefix('admin')->group(function () {
    Route::get('/accesos', [AdminController::class, 'index'])->name('admin.accesos');
    Route::get('/accesos/search', [AdminController::class, 'searchAccesosJson'])->name('admin.accesos.search');
    Route::post('/accesos', [AdminController::class, 'store'])->name('admin.accesos.store');
    Route::match(['post', 'put'], '/accesos/{id}', [AdminController::class, 'update'])->name('admin.accesos.update');
    Route::post('/accesos/{id}/edit-data', [AdminController::class, 'editData'])->name('admin.accesos.edit-data');
    Route::post('/accesos/{id}/toggle-block', [AdminController::class, 'toggleBlock'])->name('admin.accesos.toggle-block');
    Route::delete('/accesos/{id}', [AdminController::class, 'destroy'])->name('admin.accesos.destroy');
    Route::post('/sellers/{id}/toggle-mobilecheck', [AdminController::class, 'toggleMobilecheck'])->name('admin.sellers.toggle-mobilecheck');
    Route::put('/companies/{id}/offline-hours', [AdminController::class, 'updateOfflineHours'])->name('admin.companies.offline-hours');
    Route::put('/companies/{id}/reset-uuid', [AdminController::class, 'resetUuid'])->name('admin.companies.reset-uuid');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});

// Panel de gestión para managers (documentación de API)
Route::middleware(['auth', 'manager'])->prefix('admin')->group(function () {
      Route::get('/usuarios', [AdminController::class, 'usuarios'])->name('admin.usuarios');
      Route::get('/usuarios/crear', [AdminController::class, 'createUsuario'])->name('admin.usuarios.create');
      Route::post('/usuarios', [AdminController::class, 'storeUsuario'])->name('admin.usuarios.store');
      Route::get('/usuarios/{id}/editar', [AdminController::class, 'editUsuario'])->name('admin.usuarios.edit');
      Route::post('/usuarios/{id}/edit-data', [AdminController::class, 'editDataUsuario'])->name('admin.usuarios.edit-data');
      Route::put('/usuarios/{id}', [AdminController::class, 'updateUsuario'])->name('admin.usuarios.update');
      Route::delete('/usuarios/{id}', [AdminController::class, 'destroyUsuario'])->name('admin.usuarios.destroy');
      Route::get('/docs', [AdminController::class, 'docs'])->name('admin.docs');

      // Versiones de app para sincronización
      Route::get('/sync-versions', [AdminController::class, 'syncVersions'])->name('admin.sync-versions');
      Route::post('/sync-versions', [AdminController::class, 'storeSyncVersion'])->name('admin.sync-versions.store');
      Route::post('/sync-versions/{id}/edit-data', [AdminController::class, 'editSyncVersionData'])->name('admin.sync-versions.edit-data');
      Route::put('/sync-versions/{id}', [AdminController::class, 'updateSyncVersion'])->name('admin.sync-versions.update');
      Route::delete('/sync-versions/{id}', [AdminController::class, 'destroySyncVersion'])->name('admin.sync-versions.destroy');
});
