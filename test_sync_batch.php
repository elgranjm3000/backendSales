<?php

/**
 * Test del SyncController con mejoras
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║              TEST: SyncController Mejorado                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// PASO 1: LOGIN
echo "📝 PASO 1: LOGIN Y OBTENER TOKEN\n";
echo str_repeat("─", 60) . "\n";

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'sync-batch-test'
]));
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

try {
    $controller = new \App\Http\Controllers\Api\AuthController();
    $response = $controller->login($request);
    $data = json_decode($response->getContent(), true);

    if (!$data['success']) {
        die("❌ Login falló: " . ($data['message'] ?? 'Unknown error') . "\n");
    }

    $token = $data['data']['token'];
    $user = $data['data']['user'];
    $subscription = $data['data']['subscription'] ?? null;

    echo "✅ Login exitoso\n";
    echo "Usuario: {$user['email']} (Rol: {$user['role']})\n";
    echo "Plan: " . ($subscription['plan'] ?? 'N/A') . "\n";
    echo "\n";

} catch (\Exception $e) {
    die("❌ Error en login: " . $e->getMessage() . "\n");
}

// PASO 2: PROBAR ENDPOINTS DE SYNC-BATCH
echo "📡 PASO 2: PROBAR ENDPOINTS DE SYNC-BATCH\n";
echo str_repeat("─", 60) . "\n\n";

// Test 1: syncProductsBatch con datos de prueba
echo "📍 TEST 1: syncProductsBatch (5 productos)\n";

$productsRequest = Request::create('/api/sync-batch/products', 'POST', [], [], [], [], json_encode([
    'company_id' => 1,
    'products' => [
        ['code' => 'TEST001', 'name' => 'Producto Test 1', 'price' => 100],
        ['code' => 'TEST002', 'name' => 'Producto Test 2', 'price' => 200],
        ['code' => 'TEST003', 'name' => 'Producto Test 3', 'price' => 300],
        ['code' => 'TEST004', 'name' => 'Producto Test 4', 'price' => 400],
        ['code' => 'TEST005', 'name' => 'Producto Test 5', 'price' => 500],
    ]
]));
$productsRequest->headers->set('Content-Type', 'application/json');
$productsRequest->setUserResolver(function () use ($user) {
    return \App\Models\User::find($user['id']);
});

try {
    $controller = new \App\Http\Controllers\Api\SyncController();
    $response = $controller->syncProductsBatch($productsRequest);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        echo "✅ EXITOSO\n";
        echo "   Created: {$data['created']}\n";
        echo "   Updated: {$data['updated']}\n";
        echo "   Errors: {$data['errors']}\n";
    } else {
        echo "❌ ERROR: " . ($data['message'] ?? 'Unknown') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: syncProductsBatch con más de 5000 productos (debe fallar)
echo "📍 TEST 2: syncProductsBatch (5001 productos - debe fallar)\n";

$largeRequest = Request::create('/api/sync-batch/products', 'POST', [], [], [], [], json_encode([
    'company_id' => 1,
    'products' => array_fill(0, 5001, ['code' => 'TEST', 'name' => 'Test', 'price' => 100])
]));
$largeRequest->headers->set('Content-Type', 'application/json');
$largeRequest->setUserResolver(function () use ($user) {
    return \App\Models\User::find($user['id']);
});

try {
    $controller = new \App\Http\Controllers\Api\SyncController();
    $response = $controller->syncProductsBatch($largeRequest);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 422) {
        echo "✅ VALIDACIÓN CORRECTA - Límite de 5000 respetado\n";
        echo "   Mensaje: {$data['message']}\n";
        echo "   Max permitido: {$data['max_allowed']}\n";
    } else {
        echo "⚠️  Status inesperado: {$response->getStatusCode()}\n";
    }
} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: syncCategoriesBatch
echo "📍 TEST 3: syncCategoriesBatch (3 categorías)\n";

$categoriesRequest = Request::create('/api/sync-batch/categories', 'POST', [], [], [], [], json_encode([
    'company_id' => 1,
    'categories' => [
        ['name' => 'Categoría Test A', 'description' => 'Descripción A'],
        ['name' => 'Categoría Test B', 'description' => 'Descripción B'],
        ['name' => 'Categoría Test C', 'description' => 'Descripción C'],
    ]
]));
$categoriesRequest->headers->set('Content-Type', 'application/json');
$categoriesRequest->setUserResolver(function () use ($user) {
    return \App\Models\User::find($user['id']);
});

try {
    $controller = new \App\Http\Controllers\Api\SyncController();
    $response = $controller->syncCategoriesBatch($categoriesRequest);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        echo "✅ EXITOSO\n";
        echo "   Created: {$data['created']}\n";
        echo "   Updated: {$data['updated']}\n";
        echo "   Errors: {$data['errors']}\n";
    } else {
        echo "❌ ERROR: " . ($data['message'] ?? 'Unknown') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: validateCompany
echo "📍 TEST 4: validateCompany (crear nueva empresa)\n";

$companyRequest = Request::create('/api/sync-batch/company/validate', 'POST', [], [], [], [], json_encode([
    'rif' => 'J' . time(),
    'email' => 'test' . time() . '@example.com',
    'name' => 'Empresa Test Batch'
]));
$companyRequest->headers->set('Content-Type', 'application/json');
$companyRequest->setUserResolver(function () use ($user) {
    return \App\Models\User::find($user['id']);
});

try {
    $controller = new \App\Http\Controllers\Api\SyncController();
    $response = $controller->validateCompany($companyRequest);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if (in_array($response->getStatusCode(), [200, 201])) {
        echo "✅ EXITOSO\n";
        echo "   Company ID: {$data['company_id']}\n";
        echo "   RIF: {$data['company']['rif']}\n";
        echo "   Email: {$data['company']['email']}\n";
    } else {
        echo "❌ ERROR: " . ($data['message'] ?? 'Unknown') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
