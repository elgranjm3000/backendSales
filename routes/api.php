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
use App\Http\Controllers\Api\BiDirectionalSyncController;
use App\Http\Controllers\Api\SyncDataController;
use App\Http\Controllers\Api\SyncController;


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

// Dashboard con autenticación personalizada (sin redirect)
Route::get('dashboard', [DashboardController::class, 'index'])->middleware(\App\Http\Middleware\HandleApiAuth::class);


// Rutas protegidas que requieren autenticación
Route::middleware('auth:sanctum')->group(function () {
    
    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('/active-sessions', [AuthController::class, 'activeSessions']);
    });

    // Rutas de suscripciones
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\SubscriptionController::class, 'index']);
        Route::get('/active', [App\Http\Controllers\Api\SubscriptionController::class, 'active']);
        Route::get('/plans', [App\Http\Controllers\Api\SubscriptionController::class, 'plans']);
        Route::post('/', [App\Http\Controllers\Api\SubscriptionController::class, 'store']);
        Route::get('{id}', [App\Http\Controllers\Api\SubscriptionController::class, 'show']);
        Route::put('{id}', [App\Http\Controllers\Api\SubscriptionController::class, 'update']);
        Route::post('{id}/extend', [App\Http\Controllers\Api\SubscriptionController::class, 'extend']);
        Route::post('{id}/cancel', [App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        Route::post('{id}/reactivate', [App\Http\Controllers\Api\SubscriptionController::class, 'reactivate']);
    });

    // Rutas de usuarios (requieren suscripción activa)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware(\App\Http\Middleware\CheckSubscription::class);
        Route::post('/', [UserController::class, 'store'])->middleware(\App\Http\Middleware\CheckSubscription::class);
        Route::get('{id}', [UserController::class, 'show'])->middleware(\App\Http\Middleware\CheckSubscription::class);
        Route::put('{id}', [UserController::class, 'update'])->middleware(\App\Http\Middleware\CheckSubscription::class);
        Route::delete('{id}', [UserController::class, 'destroy'])->middleware(\App\Http\Middleware\CheckSubscription::class);
    });

    // Rutas de productos (requieren suscripción con feature sync_products)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
        Route::post('/', [ProductController::class, 'store']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
        Route::get('{id}', [ProductController::class, 'show']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
        Route::put('{id}', [ProductController::class, 'update']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
        Route::delete('{id}', [ProductController::class, 'destroy']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
    });

    // Rutas de categorías (requieren suscripción con feature sync_categories)
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
        Route::post('/', [CategoryController::class, 'store']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
        Route::get('{id}', [CategoryController::class, 'show']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
        Route::put('{id}', [CategoryController::class, 'update']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
        Route::delete('{id}', [CategoryController::class, 'destroy']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
    });

    // Rutas de cotizaciones (requieren suscripción con feature sync_quotes - NO disponible en trial)
    Route::prefix('quotes')->group(function () {
        Route::get('/', [QuoteController::class, 'index']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
        Route::post('/', [QuoteController::class, 'store']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
        Route::get('{id}', [QuoteController::class, 'show']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
        Route::put('{id}', [QuoteController::class, 'update']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
        Route::delete('{id}', [QuoteController::class, 'destroy']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');

        // Items de cotización
        Route::post('{id}/items', [QuoteController::class, 'addItem']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
        Route::put('{id}/items/{itemId}', [QuoteController::class, 'updateItem']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
        Route::delete('{id}/items/{itemId}', [QuoteController::class, 'deleteItem']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
    });

    // Rutas de clientes (requieren suscripción con feature sync_customers)
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
        Route::post('/', [CustomerController::class, 'store']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
        Route::get('{id}', [CustomerController::class, 'show']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
        Route::put('{id}', [CustomerController::class, 'update']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
        Route::delete('{id}', [CustomerController::class, 'destroy']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
    });

    // Rutas de compañías (requieren suscripción activa)
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');
        Route::post('/', [CompanyController::class, 'store'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');
        Route::get('{id}', [CompanyController::class, 'show'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');
        Route::put('{id}', [CompanyController::class, 'update'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');
        Route::delete('{id}', [CompanyController::class, 'destroy'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');

        // Vendedores de una compañía específica
        Route::get('{id}/sellers', [CompanyController::class, 'sellers'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');

        // Buscar en tabla acceso por id_fiscal y correo_electronico
        Route::post('/find-in-acceso', [CompanyController::class, 'findInAcceso'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');

        // Buscar compañía por RIF y email
        Route::post('/find-by-rif-email', [CompanyController::class, 'findByRifAndEmail'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':manage_companies');

        // Validar si una compañía existe
        Route::post('/validate', [CompanyController::class, 'validateCompany']);
    });

    // Rutas de vendedores (requieren suscripción con feature sync_sellers)
    Route::prefix('sellers')->group(function () {
        Route::get('/', [SellerController::class, 'index']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
        Route::post('/', [SellerController::class, 'store']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
        Route::get('{id}', [SellerController::class, 'show']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
        Route::put('{id}', [SellerController::class, 'update']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
        Route::delete('{id}', [SellerController::class, 'destroy']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');

        // Vendedores por compañía
        Route::get('company/{companyId}', [SellerController::class, 'getByCompany'])->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
    });

    // Ruta para obtener información del usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rutas de sincronización bidireccional PostgreSQL ↔ MySQL
    Route::prefix('sync-v2')->middleware(['throttle.sync:10,1'])->group(function () {
        // Sincronizar entidades individuales
        Route::post('/products', [BiDirectionalSyncController::class, 'syncProducts']);
        Route::post('/customers', [BiDirectionalSyncController::class, 'syncCustomers']);
        Route::post('/quotes', [BiDirectionalSyncController::class, 'syncQuotes']);
        Route::post('/sellers', [BiDirectionalSyncController::class, 'syncSellers']);
        Route::post('/categories', [BiDirectionalSyncController::class, 'syncCategories']);

        // Sincronizar todo
        Route::post('/all', [BiDirectionalSyncController::class, 'syncAll']);

        // Estadísticas y estado
        Route::get('/stats', [BiDirectionalSyncController::class, 'getStats']);
        Route::get('/queue-status', [BiDirectionalSyncController::class, 'getQueueStatus']);
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

// Endpoints de sincronización de datos para Python (SÍNCRONOS)
// Usan autenticación Sanctum + verificación de suscripción
Route::middleware(['auth:sanctum'])->prefix('sync-data')->group(function () {
    // Sincronizar productos (requiere feature sync_products)
    Route::post('/products', [SyncDataController::class, 'syncProducts'])
        ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');

    // Sincronizar customers (requiere feature sync_customers)
    Route::post('/customers', [SyncDataController::class, 'syncCustomers'])
        ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');

    // Sincronizar quotes (requiere feature sync_quotes - NO disponible en trial)
    Route::post('/quotes', [SyncDataController::class, 'syncQuotes'])
        ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');

    // Sincronizar sellers (requiere feature sync_sellers)
    Route::post('/sellers', [SyncDataController::class, 'syncSellers'])
        ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');

    // Sincronizar categories (requiere feature sync_categories)
    Route::post('/categories', [SyncDataController::class, 'syncCategories']);
        //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');

    // Obtener productos (requiere suscripción activa, sin feature específico)
    Route::get('/products', [SyncDataController::class, 'getProducts']); //->middleware(\App\Http\Middleware\CheckSubscription::class);

    // Obtener customers (requiere suscripción activa, sin feature específico)
    Route::get('/customers', [SyncDataController::class, 'getCustomers']); //  ->middleware(\App\Http\Middleware\CheckSubscription::class);

    // Obtener sellers (requiere suscripción activa, sin feature específico)
    Route::get('/sellers', [SyncDataController::class, 'getSellers']); //        ->middleware(\App\Http\Middleware\CheckSubscription::class);
});

// Endpoints de sincronización por lotes (Batch Sync)
// Usan autenticación Sanctum + verificación de suscripción
Route::middleware(['auth:sanctum'])->prefix('sync-batch')->group(function () {
    // Company (NO requiere suscripción - crea suscripción trial automáticamente)
    Route::post('/company/validate', [SyncController::class, 'validateCompany']);

    // Products (requiere feature sync_products)
    Route::get('/products', [SyncController::class, 'getProducts'])
        ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
    Route::post('/products', [SyncController::class, 'syncProductsBatch']); //  ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');
    Route::delete('/products', [SyncController::class, 'destroyProductsBatch']); //      ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_products');

    // Customers (requiere feature sync_customers)
    Route::get('/customers', [SyncController::class, 'getCustomers']);//->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
    Route::post('/customers', [SyncController::class, 'syncCustomersBatch']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');
    Route::delete('/customers', [SyncController::class, 'destroyCustomersBatch']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_customers');

    // Categories (requiere feature sync_categories)
    Route::get('/categories', [SyncController::class, 'getCategories']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
    Route::post('/categories', [SyncController::class, 'syncCategoriesBatch']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');
    Route::delete('/categories', [SyncController::class, 'destroyCategoriesBatch']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_categories');

    // Sellers (requiere feature sync_sellers)
    Route::get('/sellers', [SyncController::class, 'getSellers']);
        //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
    Route::post('/sellers', [SyncController::class, 'syncSellersBatch']);
        //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');
    Route::delete('/sellers', [SyncController::class, 'destroySellersBatch']);
        //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_sellers');

    // Quotes (requiere feature sync_quotes - NO disponible en trial)
    Route::post('/quotes', [SyncController::class, 'createQuote']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
    Route::get('/quotes', [SyncController::class, 'getQuotes']); //->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
    Route::put('/quotes/{id}/status', [SyncController::class, 'updateQuoteStatus']); //    ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
    Route::delete('/quotes/{id}', [SyncController::class, 'destroyQuote'])
        ->middleware(\App\Http\Middleware\CheckSubscription::class . ':sync_quotes');
   
    Route::get('/history', [SyncController::class, 'getSyncHistory']);
    Route::get('/last-sync', [SyncController::class, 'getLastSync']);


});

// Endpoints de sincronización para clientes externos (autenticados por API key de acceso)
Route::prefix('sync-client')->middleware(['auth.acceso', 'throttle.acceso:100,1'])->group(function () {
    
    /*Route::post('/products', [SyncDataController::class, 'syncProducts']);
    Route::post('/customers', [SyncDataController::class, 'syncCustomers']);
    Route::post('/quotes', [SyncDataController::class, 'syncQuotes']);
    Route::post('/sellers', [SyncDataController::class, 'syncSellers']);
    Route::post('/categories', [SyncDataController::class, 'syncCategories']);

    Route::get('/products', [SyncDataController::class, 'getProducts']);
    Route::get('/customers', [SyncDataController::class, 'getCustomers']);
    Route::get('/sellers', [SyncDataController::class, 'getSellers']);*/

    // Endpoint para que el cliente verifique su conexión
    Route::get('/ping', function () {
        $acceso = request()->attributes->get('acceso_company');
        return response()->json([
            'success' => true,
            'message' => 'Conexión exitosa',
            'data' => [
                'id' => $acceso->id,
                'empresa' => $acceso->nombre,
                'rif' => $acceso->codigo,
                'email' => $acceso->correo_electronico,
            ]
        ]);
    });

    // --- Endpoints batch (equivalentes a sync-batch pero con API key de acceso) ---

    // Products
    
    Route::post('/company/validate', [SyncController::class, 'validateCompany']);

    Route::get('/batch/products', [SyncController::class, 'getProducts']);
    Route::post('/batch/products', [SyncController::class, 'syncProductsBatch']);
    Route::delete('/batch/products', [SyncController::class, 'destroyProductsBatch']);

    // Customers
    Route::get('/batch/customers', [SyncController::class, 'getCustomers']);
    Route::post('/batch/customers', [SyncController::class, 'syncCustomersBatch']);
    Route::delete('/batch/customers', [SyncController::class, 'destroyCustomersBatch']);

    // Categories
    Route::get('/batch/categories', [SyncController::class, 'getCategories']);
    Route::post('/batch/categories', [SyncController::class, 'syncCategoriesBatch']);
    Route::delete('/batch/categories', [SyncController::class, 'destroyCategoriesBatch']);

    // Sellers
    Route::get('/batch/sellers', [SyncController::class, 'getSellers']);
    Route::post('/batch/sellers', [SyncController::class, 'syncSellersBatch']);
    Route::delete('/batch/sellers', [SyncController::class, 'destroySellersBatch']);

    // Quotes
    Route::get('/batch/quotes', [SyncController::class, 'getQuotes']);
    Route::post('/batch/quotes', [SyncController::class, 'createQuote']);
    Route::put('/batch/quotes/{id}/status', [SyncController::class, 'updateQuoteStatus']);
    Route::delete('/batch/quotes/{id}', [SyncController::class, 'destroyQuote']);

    // Sync history
    Route::get('/batch/history', [SyncController::class, 'getSyncHistory']);
    Route::get('/batch/last-sync', [SyncController::class, 'getLastSync']);
});