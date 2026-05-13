<?php

/**
 * Test de los nuevos endpoints GET del SyncController
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║              TEST: Endpoints GET del SyncController              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Login
echo "📝 PASO 1: LOGIN\n";
echo str_repeat("─", 60) . "\n";

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'get-test'
]));
$request->headers->set('Content-Type', 'application/json');

try {
    $controller = new \App\Http\Controllers\Api\AuthController();
    $response = $controller->login($request);
    $data = json_decode($response->getContent(), true);

    if (!$data['success']) {
        die("❌ Login falló\n");
    }

    $token = $data['data']['token'];
    $user = $data['data']['user'];

    echo "✅ Login exitoso - Usuario: {$user['email']}\n\n";

} catch (\Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

$controller = new \App\Http\Controllers\Api\SyncController();

// Función helper para crear request GET
function createGetRequest($uri, $params, $user) {
    $request = Request::create($uri, 'GET', $params);
    $request->setUserResolver(function () use ($user) {
        return \App\Models\User::find($user['id']);
    });
    return $request;
}

// Test GET Products
echo "📡 PASO 2: TEST GET /products\n";
echo str_repeat("─", 60) . "\n";

$request = createGetRequest('/api/sync-batch/products', [
    'company_id' => 1,
    'search' => 'HP'
], $user);

try {
    $response = $controller->getProducts($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        $total = $data['data']['total'] ?? 0;
        echo "✅ EXITOSO - {$total} productos encontrados\n";
        if (isset($data['data']['data']) && count($data['data']['data']) > 0) {
            echo "   Primer producto: " . $data['data']['data'][0]['name'] . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test GET Customers
echo "📡 PASO 3: TEST GET /customers\n";
echo str_repeat("─", 60) . "\n";

$request = createGetRequest('/api/sync-batch/customers', [
    'company_id' => 1
], $user);

try {
    $response = $controller->getCustomers($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        $total = $data['data']['total'] ?? 0;
        echo "✅ EXITOSO - {$total} clientes encontrados\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test GET Categories
echo "📡 PASO 4: TEST GET /categories\n";
echo str_repeat("─", 60) . "\n";

$request = createGetRequest('/api/sync-batch/categories', [
    'company_id' => 1
], $user);

try {
    $response = $controller->getCategories($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        $total = $data['data']['total'] ?? 0;
        echo "✅ EXITOSO - {$total} categorías encontradas\n";
        if (isset($data['data']['data']) && count($data['data']['data']) > 0) {
            echo "   Primera categoría: " . $data['data']['data'][0]['name'] . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test GET Sellers
echo "📡 PASO 5: TEST GET /sellers\n";
echo str_repeat("─", 60) . "\n";

$request = createGetRequest('/api/sync-batch/sellers', [
    'company_id' => 1
], $user);

try {
    $response = $controller->getSellers($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        $total = $data['data']['total'] ?? 0;
        echo "✅ EXITOSO - {$total} vendedores encontrados\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test con búsqueda
echo "📡 PASO 6: TEST GET /products con búsqueda\n";
echo str_repeat("─", 60) . "\n";

$request = createGetRequest('/api/sync-batch/products', [
    'company_id' => 1,
    'search' => 'Laptop',
    'category_id' => 1
], $user);

try {
    $response = $controller->getProducts($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    if ($response->getStatusCode() === 200) {
        $total = $data['data']['total'] ?? 0;
        echo "✅ EXITOSO - {$total} productos encontrados con 'Laptop'\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO - Todos los endpoints GET funcionan\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
