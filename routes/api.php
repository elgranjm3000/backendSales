<?php
// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\CategoryController;


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

Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/users/check', [AuthController::class, 'checkCompanyInfo']);
Route::post('/users/stepConfirm', [AuthController::class, 'confirmCompanyRegistration']);
Route::post('/users/validateCompanyCode', [AuthController::class, 'validateCompanyCode']);
Route::post('/users/completeCompanyRegistration', [AuthController::class, 'completeCompanyRegistration']);


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

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('{id}', [ProductController::class, 'show']);
        Route::put('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'destroy']);
        
        // Vendedores de una compañía específica
    });
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('{id}', [CategoryController::class, 'show']);
        Route::put('{id}', [CategoryController::class, 'update']);
        Route::delete('{id}', [CategoryController::class, 'destroy']);
        
        // Vendedores de una compañía específica
    });

    Route::prefix('quotes')->group(function () {
        Route::get('/', [QuoteController::class, 'index']);
        Route::post('/', [QuoteController::class, 'store']);
        Route::get('{id}', [QuoteController::class, 'show']);
        Route::put('{id}', [QuoteController::class, 'update']);
        Route::delete('{id}', [QuoteController::class, 'destroy']);
        
        // Vendedores de una compañía específica
    });

    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('{id}', [CustomerController::class, 'show']);
        Route::put('{id}', [CustomerController::class, 'update']);
        Route::delete('{id}', [CustomerController::class, 'destroy']);
        
        // Vendedores de una compañía específica
    });

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