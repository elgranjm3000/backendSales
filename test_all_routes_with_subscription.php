<?php

/**
 * Script para probar todas las rutas con suscripción
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "=== PRUEBA COMPLETA DE RUTAS CON SUSCRIPCIÓN ===\n\n";

// 1. Obtener usuario y suscripción
$user = \App\Models\User::where('email', 'admin@test.com')->first();
if (!$user) {
    die("❌ Usuario admin@test.com no encontrado\n");
}

$subscription = $user->activeSubscription();
$company = $user->companies->first();

echo "👤 Usuario: {$user->email}\n";
echo "📦 Plan: {$subscription->plan} ({$subscription->plan_name})\n";
echo "⏰ Expira: {$subscription->expires_at}\n";
echo "✅ Activa: " . ($subscription->isActive() ? 'SÍ' : 'NO') . "\n";
echo "\n";

// Crear token
$tokenResult = $user->createToken('test-full-routes', ['*'], $subscription->expires_at);
$token = $tokenResult->plainTextToken;

echo "🔑 Token: " . substr($token, 0, 40) . "...\n\n";

// Definir pruebas
$tests = [
    // Productos - TRIAL tiene sync_products ✅
    [
        'name' => 'GET /api/products',
        'method' => 'GET',
        'endpoint' => '/api/products',
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_products)',
    ],
    [
        'name' => 'POST /api/products',
        'method' => 'POST',
        'endpoint' => '/api/products',
        'data' => ['name' => 'Test', 'price' => 100],
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_products)',
    ],

    // Clientes - TRIAL tiene sync_customers ✅
    [
        'name' => 'GET /api/customers',
        'method' => 'GET',
        'endpoint' => '/api/customers',
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_customers)',
    ],
    [
        'name' => 'POST /api/customers',
        'method' => 'POST',
        'endpoint' => '/api/customers',
        'data' => ['name' => 'Test Customer', 'company_id' => $company->id],
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_customers)',
    ],

    // Categorías - TRIAL tiene sync_categories ✅
    [
        'name' => 'GET /api/categories',
        'method' => 'GET',
        'endpoint' => '/api/categories',
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_categories)',
    ],
    [
        'name' => 'POST /api/categories',
        'method' => 'POST',
        'endpoint' => '/api/categories',
        'data' => ['name' => 'Test Category', 'company_id' => $company->id],
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_categories)',
    ],

    // Vendedores - TRIAL tiene sync_sellers ✅
    [
        'name' => 'GET /api/sellers',
        'method' => 'GET',
        'endpoint' => '/api/sellers',
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_sellers)',
    ],
    [
        'name' => 'POST /api/sellers',
        'method' => 'POST',
        'endpoint' => '/api/sellers',
        'data' => ['name' => 'Test Seller', 'code' => 'TEST001', 'company_id' => $company->id],
        'expected' => '✅ Debe funcionar (TRIAL tiene sync_sellers)',
    ],

    // Cotizaciones - TRIAL NO tiene sync_quotes ❌
    [
        'name' => 'GET /api/quotes',
        'method' => 'GET',
        'endpoint' => '/api/quotes',
        'expected' => '❌ Debe fallar 403 (TRIAL NO tiene sync_quotes)',
    ],
    [
        'name' => 'POST /api/quotes',
        'method' => 'POST',
        'endpoint' => '/api/quotes',
        'data' => ['quote_number' => 'TEST-001', 'customer_id' => 1, 'total' => 100],
        'expected' => '❌ Debe fallar 403 (TRIAL NO tiene sync_quotes)',
    ],

    // Compañías - Requiere suscripción activa (sin feature específico) ✅
    [
        'name' => 'GET /api/companies',
        'method' => 'GET',
        'endpoint' => '/api/companies',
        'expected' => '✅ Debe funcionar (solo requiere suscripción activa)',
    ],

    // Sync Data - Con verificación de suscripción
    [
        'name' => 'GET /api/sync-data/products',
        'method' => 'GET',
        'endpoint' => '/api/sync-data/products?company_id=' . $company->id,
        'expected' => '✅ Debe funcionar (suscripción activa)',
    ],
    [
        'name' => 'POST /api/sync-data/quotes',
        'method' => 'POST',
        'endpoint' => '/api/sync-data/quotes',
        'data' => ['company_id' => $company->id, 'quotes' => []],
        'expected' => '❌ Debe fallar 403 (TRIAL NO tiene sync_quotes)',
    ],
];

$passed = 0;
$failed = 0;
$total = count($tests);

foreach ($tests as $i => $test) {
    $num = $i + 1;
    echo "TEST {$num}: {$test['name']}";
    echo "\nEsperado: {$test['expected']}\n";

    try {
        $data = $test['data'] ?? [];
        if ($test['method'] === 'GET') {
            $endpoint = $test['endpoint'];
            if (!empty($data)) {
                $endpoint .= '?' . http_build_query($data);
            }
            $request = Request::create($endpoint, 'GET', [], [], [], [],
                json_encode($data));
        } else {
            $request = Request::create($test['endpoint'], $test['method'], [], [], [], [],
                json_encode($data));
        }

        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = app()->handle($request);
        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getContent(), true);

        echo "Resultado: HTTP {$statusCode}\n";

        // Evaluar resultado
        $testPassed = false;

        if ($statusCode === 200 || $statusCode === 201) {
            // Tests que deberían funcionar
            if (strpos($test['expected'], '✅') !== false) {
                echo "✅ EXITOSO\n";
                $testPassed = true;
                $passed++;
            } else {
                echo "❌ ERROR: Debería haber fallado pero funcionó\n";
                $failed++;
            }
        } elseif ($statusCode === 403) {
            // Tests que deberían fallar
            if (strpos($test['expected'], '❌') !== false) {
                $errorMsg = $content['message'] ?? 'Unknown error';
                echo "✅ BLOQUEADO CORRECTAMENTE: {$errorMsg}\n";
                if (isset($content['data']['current_plan'])) {
                    echo "   Plan actual: {$content['data']['current_plan']}\n";
                    echo "   Plan requerido: {$content['data']['required_plan']}\n";
                }
                $testPassed = true;
                $passed++;
            } else {
                echo "❌ ERROR: Debería funcionar pero fue bloqueado\n";
                echo "   Mensaje: {$content['message']}\n";
                $failed++;
            }
        } elseif ($statusCode === 401) {
            echo "❌ NO AUTENTICADO (error en token)\n";
            $failed++;
        } else {
            echo "⚠️  Código inesperado: {$statusCode}\n";
            if (isset($content['message'])) {
                echo "   Mensaje: {$content['message']}\n";
            }
            $failed++;
        }

    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        $failed++;
    }

    echo str_repeat("-", 70) . "\n\n";
}

// Resumen
echo "\n" . str_repeat("=", 70) . "\n";
echo "RESUMEN DE PRUEBAS\n";
echo str_repeat("=", 70) . "\n";
echo "Total:  {$total}\n";
echo "✅ Pasaron:  {$passed}\n";
echo "❌ Fallaron: {$failed}\n";
echo "Porcentaje: " . round(($passed / $total) * 100, 1) . "%\n";

// Features del plan actual
echo "\n" . str_repeat("=", 70) . "\n";
echo "FEATURES DEL PLAN TRIAL\n";
echo str_repeat("=", 70) . "\n";
foreach ($subscription->features as $feature => $enabled) {
    echo sprintf("%s %s\n", $enabled ? '✅' : '❌', $feature);
}

// Limpiar
$user->tokens()->where('name', 'test-full-routes')->delete();
echo "\n🧹 Token de prueba eliminado\n";

if ($passed === $total) {
    echo "\n🎉 ¡TODAS LAS PRUEBAS PASARON!\n";
} else {
    echo "\n⚠️  Algunas pruebas fallaron. Revisa los errores arriba.\n";
}
