<?php

/**
 * Test completo del flujo de autenticación y suscripciones
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         TEST COMPLETO: AUTH + SUBSCRIPTION + ENDPOINTS        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// PASO 1: LOGIN
echo "📝 PASO 1: LOGIN Y OBTENER TOKEN\n";
echo str_repeat("─", 60) . "\n";

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'test-script'
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
    echo "🔑 Token: " . substr($token, 0, 50) . "...\n";
    echo "👤 Usuario: {$user['email']} (Rol: {$user['role']})\n";

    if ($subscription) {
        echo "📦 Suscripción:\n";
        echo "   Plan: {$subscription['plan']}\n";
        echo "   Status: {$subscription['status']}\n";
        echo "   Días restantes: {$subscription['days_remaining']}\n";
        echo "   Features: " . implode(', ', array_keys($subscription['features'] ?? [])) . "\n";
    }
    echo "\n";

} catch (\Exception $e) {
    die("❌ Error en login: " . $e->getMessage() . "\n");
}

// PASO 2: PROBAR ENDPOINTS DIRECTAMENTE (sin HTTP)
echo "📡 PASO 2: PROBAR ENDPOINTS (Direct Call)\n";
echo str_repeat("─", 60) . "\n\n";

$endpoints = [
    [
        'name' => 'GET /api/companies',
        'controller' => '\App\Http\Controllers\Api\CompanyController',
        'method' => 'index',
        'feature' => null,
        'should_work' => true
    ],
    [
        'name' => 'GET /api/products',
        'controller' => '\App\Http\Controllers\Api\ProductController',
        'method' => 'index',
        'feature' => 'sync_products',
        'should_work' => true
    ],
    [
        'name' => 'GET /api/categories',
        'controller' => '\App\Http\Controllers\Api\CategoryController',
        'method' => 'index',
        'feature' => 'sync_categories',
        'should_work' => true
    ],
    [
        'name' => 'GET /api/quotes',
        'controller' => '\App\Http\Controllers\Api\QuoteController',
        'method' => 'index',
        'feature' => 'sync_quotes',
        'should_work' => false // Plan trial no tiene sync_quotes
    ],
];

foreach ($endpoints as $endpoint) {
    echo "📍 {$endpoint['name']}\n";
    echo "   Feature requerida: " . ($endpoint['feature'] ?: 'N/A') . "\n";
    echo "   Debe funcionar: " . ($endpoint['should_work'] ? 'Sí' : 'No') . "\n";

    try {
        // Crear request simulado con usuario autenticado
        $request = Request::create($endpoint['name'], 'GET');
        $request->setUserResolver(function () use ($user) {
            return \App\Models\User::find($user['id']);
        });

        $controller = new $endpoint['controller']();
        $response = $controller->{$endpoint['method']}($request);
        $data = json_decode($response->getContent(), true);

        $status = $response->getStatusCode();
        echo "   Status: {$status}\n";

        if ($status === 200) {
            $count = isset($data['data']) ? (is_array($data['data']) ? count($data['data']) : 1) : 0;
            echo "   ✅ EXITOSO - {$count} registros\n";
        } elseif ($status === 403) {
            echo "   ❌ BLOQUEADO - " . ($data['message'] ?? 'Forbidden') . "\n";
        } else {
            echo "   ⚠️  Status inesperado\n";
            echo "   Mensaje: " . ($data['message'] ?? 'No message') . "\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// PASO 3: PROBAR ENDPOINT DE BÚSQUEDA EN ACCESO
echo "🔍 PASO 3: PROBAR ENDPOINT findInAcceso\n";
echo str_repeat("─", 60) . "\n";

$request = Request::create('/api/companies/find-in-acceso', 'POST', [], [], [], [], json_encode([
    'id_fiscal' => 'J123456789',
    'correo_electronico' => 'test@example.com'
]));
$request->headers->set('Content-Type', 'application/json');
$request->setUserResolver(function () use ($user) {
    return \App\Models\User::find($user['id']);
});

try {
    $controller = new \App\Http\Controllers\Api\CompanyController();
    $response = $controller->findInAcceso($request);
    $data = json_decode($response->getContent(), true);

    $status = $response->getStatusCode();
    echo "Status: {$status}\n";

    if ($status === 404) {
        echo "✅ Endpoint funciona correctamente (registro no encontrado - es esperado)\n";
        echo "Mensaje: {$data['message']}\n";
    } elseif ($status === 200) {
        echo "✅ Registro encontrado\n";
        echo "ID Fiscal: {$data['data']['id_fiscal']}\n";
    } else {
        echo "Respuesta: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
