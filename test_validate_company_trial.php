<?php

/**
 * Test de validateCompany con suscripción TRIAL automática
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     TEST: validateCompany con Suscripción TRIAL automática      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Limpiar sesiones activas primero
echo "🧹 Limpiando sesiones activas...\n";
$user = \App\Models\User::where('email', 'admin@test.com')->first();
if ($user) {
    $user->terminateAllSessions();
    echo "✅ Sesiones terminadas\n\n";
}

// Login
echo "📝 PASO 1: LOGIN\n";
echo str_repeat("─", 60) . "\n";

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'validate-company-test'
]));
$request->headers->set('Content-Type', 'application/json');

try {
    $controller = new \App\Http\Controllers\Api\AuthController();
    $response = $controller->login($request);
    $data = json_decode($response->getContent(), true);

    if (!$data['success']) {
        echo "Respuesta completa:\n";
        print_r($data);
        die("\n❌ Login falló\n");
    }

    $token = $data['data']['token'];
    $user = $data['data']['user'];

    echo "✅ Login exitoso - Usuario: {$user['email']}\n\n";

} catch (\Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
}

$controller = new \App\Http\Controllers\Api\SyncController();

// Función helper para crear request
function createRequest($uri, $method, $data, $user) {
    $request = Request::create($uri, $method, [], [], [], [], json_encode($data));
    $request->headers->set('Content-Type', 'application/json');
    $request->setUserResolver(function () use ($user) {
        return \App\Models\User::find($user['id']);
    });
    return $request;
}

// Test 1: Crear nueva compañía con suscripción TRIAL
echo "📡 PASO 2: CREAR NUEVA COMPAÑÍA (con TRIAL automático)\n";
echo str_repeat("─", 60) . "\n";

$rif = 'J' . rand(10000000, 99999999);
$request = createRequest('/api/sync-batch/company/validate', 'POST', [
    'rif' => $rif,
    'email' => 'test_' . time() . '@example.com',
    'name' => 'Empresa Test TRIAL'
], $user);

try {
    $response = $controller->validateCompany($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    echo "✅ Compañía creada - ID: {$data['company_id']}\n";
    echo "   Nombre: {$data['company']['name']}\n";
    echo "   RIF: {$data['company']['rif']}\n";

    if (isset($data['subscription'])) {
        echo "\n📦 SUSCRIPCIÓN CREADA:\n";
        echo "   Plan: {$data['subscription']['plan']}\n";
        echo "   Status: {$data['subscription']['status']}\n";
        echo "   Inicio: {$data['subscription']['starts_at']}\n";
        echo "   Expira: {$data['subscription']['expires_at']}\n";
        echo "   Días restantes: {$data['subscription']['days_remaining']}\n";
        echo "\n   Features:\n";
        foreach ($data['subscription']['features'] as $feature => $enabled) {
            $status = $enabled ? '✅' : '❌';
            echo "   {$status} {$feature}\n";
        }
    }

    $companyId = $data['company_id'];

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 2: Validar compañía existente
echo "📡 PASO 3: VALIDAR COMPAÑÍA EXISTENTE\n";
echo str_repeat("─", 60) . "\n";

$request = createRequest('/api/sync-batch/company/validate', 'POST', [
    'rif' => $rif,
    'email' => 'test_' . time() . '@example.com',
], $user);

try {
    $response = $controller->validateCompany($request);
    $data = json_decode($response->getContent(), true);

    echo "Status: {$response->getStatusCode()}\n";
    echo "✅ Compañía validada - ID: {$data['company_id']}\n";
    echo "   Mensaje: {$data['message']}\n";

    if (isset($data['subscription']) && $data['subscription']) {
        echo "\n📦 SUSCRIPCIÓN EXISTENTE:\n";
        echo "   Plan: {$data['subscription']['plan']}\n";
        echo "   Status: {$data['subscription']['status']}\n";
        echo "   Expira: {$data['subscription']['expires_at']}\n";
    } else {
        echo "\n⚠️  No tiene suscripción activa\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
