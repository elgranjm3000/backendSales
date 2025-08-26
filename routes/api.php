<?php
// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/



// Rutas protegidas con autenticación
/*Route::middleware('auth:sanctum')->group(function () {
    
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
        Route::post('quotes', [SyncController::class, 'syncQuotes']); // Cambio de sales a quotes
    });

    // CRUD Productos
    Route::apiResource('products', ProductController::class);

    // CRUD Clientes
    Route::apiResource('customers', CustomerController::class);

    // CRUD Presupuestos
    Route::apiResource('quotes', QuoteController::class);
    
    // Acciones específicas de presupuestos
    Route::prefix('quotes/{quote}')->group(function () {
        Route::post('send', [QuoteController::class, 'send']);
        Route::post('approve', [QuoteController::class, 'approve']);
        Route::post('reject', [QuoteController::class, 'reject']);
        Route::post('duplicate', [QuoteController::class, 'duplicate']);
    });

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
});*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});
Route::post('/users/register', [AuthController::class, 'register']);


// Rutas protegidas que requieren autenticación
Route::middleware('auth:sanctum')->group(function () {
    
    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Rutas de usuarios
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
    });

    Route::get('dashboard', [DashboardController::class, 'index']);


    // Rutas de compañías
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('{id}', [CompanyController::class, 'show']);
        Route::put('{id}', [CompanyController::class, 'update']);
        Route::delete('{id}', [CompanyController::class, 'destroy']);
        
        // Vendedores de una compañía específica
        Route::get('{id}/sellers', [CompanyController::class, 'sellers']);
    });

    // Rutas de vendedores
    Route::prefix('sellers')->group(function () {
        Route::get('/', [SellerController::class, 'index']);
        Route::post('/', [SellerController::class, 'store']);
        Route::get('{id}', [SellerController::class, 'show']);
        Route::put('{id}', [SellerController::class, 'update']);
        Route::delete('{id}', [SellerController::class, 'destroy']);
        
        // Vendedores por compañía
        Route::get('company/{companyId}', [SellerController::class, 'getByCompany']);
    });

    // Ruta para obtener información del usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Ruta de verificación de salud de la API
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'module' => 'quotes' // Indicar que es módulo de presupuestos
    ]);
});