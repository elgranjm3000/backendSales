<?php

/**
 * Test para verificar el fix de syncSellersBatch
 * Prueba la sincronización de vendedores con el mismo user_id pero diferente code
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     TEST: syncSellersBatch - Fix unique constraint            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Login
echo "📝 PASO 1: LOGIN\n";
echo str_repeat("─", 60) . "\n";

$user = \App\Models\User::where('email', 'admin@test.com')->first();
if ($user) {
    $user->terminateAllSessions();
}

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'test-sellers-fix'
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
    $userId = $data['data']['user']['id'];
    echo "✅ Login exitoso - User ID: {$userId}\n\n";

} catch (\Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// Obtener compañía
$company = \App\Models\Company::where('user_id', $userId)->first();
if (!$company) {
    die("❌ No hay compañía para el usuario\n");
}

$companyId = $company->id;
echo "🏢 Company ID: {$companyId}\n\n";

// Crear un usuario de prueba para el seller
echo "👤 PASO 2: CREAR USUARIO DE PRUEBA PARA SELLER\n";
echo str_repeat("─", 60) . "\n";

$testEmail = 'seller_test_' . time() . '@example.com';
$testPassword = password_hash('password123', PASSWORD_BCRYPT);

$testUser = \App\Models\User::where('email', $testEmail)->first();
if (!$testUser) {
    $testUser = \App\Models\User::create([
        'name' => 'Seller Test',
        'email' => $testEmail,
        'password' => $testPassword,
        'role' => 'seller',
        'status' => 'active',
    ]);
    echo "✅ Usuario creado - ID: {$testUser->id}, Email: {$testEmail}\n\n";
} else {
    echo "ℹ️  Usuario ya existe - ID: {$testUser->id}\n\n";
}

// PASO 3: Primera sincronización (code="TEST-001")
echo "📡 PASO 3: PRIMERA SINCRONIZACIÓN (code=TEST-001)\n";
echo str_repeat("─", 60) . "\n";

$controller = new \App\Http\Controllers\Api\SyncController();

$request = Request::create('/api/sync-batch/sellers', 'POST', [], [], [], [], json_encode([
    'company_id' => $companyId,
    'sellers' => [
        [
            'code' => 'TEST-001',
            'description' => 'Vendedor Test 001',
            'email' => $testEmail,
            'password' => $testPassword,
            'status' => 'active',
        ]
    ]
]));
$request->headers->set('Content-Type', 'application/json');
$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $response = $controller->syncSellersBatch($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Created: {$data['created']}\n";
    echo "Updated: {$data['updated']}\n";
    echo "Errors: {$data['errors']}\n";

    if ($data['created'] > 0) {
        echo "✅ Primer seller creado exitosamente\n\n";
    } else {
        echo "⚠️  No se creó (quizás ya existía)\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// PASO 4: Segunda sincronización con MISMO user_id pero diferente code
echo "📡 PASO 4: SEGUNDA SINCRONIZACIÓN (code=TEST-002) MISMO USUARIO\n";
echo str_repeat("─", 60) . "\n";
echo "Este es el escenario que causaba el error 23505\n";
echo "Mismo usuario ({$testEmail}), diferente código (TEST-002)\n\n";

$request = Request::create('/api/sync-batch/sellers', 'POST', [], [], [], [], json_encode([
    'company_id' => $companyId,
    'sellers' => [
        [
            'code' => 'TEST-002',  // ← Código diferente
            'description' => 'Vendedor Test 002 (actualizado)',
            'email' => $testEmail,  // ← Mismo email (mismo user_id)
            'password' => $testPassword,
            'status' => 'active',
        ]
    ]
]));
$request->headers->set('Content-Type', 'application/json');
$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $response = $controller->syncSellersBatch($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Created: {$data['created']}\n";
    echo "Updated: {$data['updated']}\n";
    echo "Errors: {$data['errors']}\n";

    if ($data['errors'] > 0) {
        echo "\n❌ ERRORES:\n";
        foreach ($data['error_details'] as $error) {
            echo "   - Index {$error['index']}: {$error['error']}\n";
        }
    } elseif ($data['updated'] > 0) {
        echo "\n✅ EXITO: Seller actualizado (UPDATE en lugar de INSERT)\n";
        echo "   Esto evita la violación de restricción única\n";
    } else {
        echo "\n⚠️  Inesperado: Created={$data['created']}, Updated={$data['updated']}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// PASO 5: Verificar estado final
echo "\n📊 PASO 5: VERIFICAR ESTADO FINAL EN BD\n";
echo str_repeat("─", 60) . "\n";

$seller = \App\Models\Seller::where('user_id', $testUser->id)
    ->where('company_id', $companyId)
    ->first();

if ($seller) {
    echo "✅ Seller encontrado:\n";
    echo "   ID: {$seller->id}\n";
    echo "   User ID: {$seller->user_id}\n";
    echo "   Company ID: {$seller->company_id}\n";
    echo "   Code: {$seller->code}\n";
    echo "   Description: {$seller->description}\n";
    echo "   Status: {$seller->status}\n";
    echo "\n";

    // Verificar que el código se actualizó
    if ($seller->code === 'TEST-002') {
        echo "✅ EXITO TOTAL: El código se actualizó de TEST-001 a TEST-002\n";
        echo "   Sin violar la restricción única (user_id, company_id)\n";
    } else {
        echo "⚠️  El código es {$seller->code} (se esperaba TEST-002)\n";
    }

    // Verificar que solo hay un seller para este user+company
    $count = \App\Models\Seller::where('user_id', $testUser->id)
        ->where('company_id', $companyId)
        ->count();

    echo "   Total sellers para (user_id={$testUser->id}, company_id={$companyId}): {$count}\n";

    if ($count === 1) {
        echo "✅ Correcto: Solo hay 1 seller (no duplicados)\n";
    } else {
        echo "❌ Error: Hay {$count} sellers (debería ser 1)\n";
    }

} else {
    echo "❌ No se encontró el seller\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
