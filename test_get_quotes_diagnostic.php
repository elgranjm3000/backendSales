<?php

/**
 * Test de diagnóstico para GET /api/sync-batch/quotes
 * Verifica autenticación, suscripción y permisos
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     TEST: GET /api/sync-batch/quotes - Diagnóstico 403        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Paso 1: Limpiar sesiones y hacer login
echo "📝 PASO 1: LOGIN\n";
echo str_repeat("─", 60) . "\n";

$user = \App\Models\User::where('email', 'admin@test.com')->first();
if ($user) {
    $user->terminateAllSessions();
}

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'test-quotes'
]));
$request->headers->set('Content-Type', 'application/json');

try {
    $controller = new \App\Http\Controllers\Api\AuthController();
    $response = $controller->login($request);
    $data = json_decode($response->getContent(), true);

    if (!$data['success']) {
        echo "❌ Login falló:\n";
        print_r($data);
        exit(1);
    }

    $token = $data['data']['token'];
    $userId = $data['data']['user']['id'];

    echo "✅ Login exitoso\n";
    echo "   User ID: {$userId}\n";
    echo "   Token: " . substr($token, 0, 20) . "...\n\n";

} catch (\Exception $e) {
    echo "❌ Error en login: " . $e->getMessage() . "\n";
    exit(1);
}

// Paso 2: Verificar suscripciones del usuario
echo "📦 PASO 2: VERIFICAR SUSCRIPCIONES\n";
echo str_repeat("─", 60) . "\n";

$user = \App\Models\User::find($userId);
$subscriptions = \App\Models\Subscription::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Suscripciones encontradas: {$subscriptions->count()}\n\n";

if ($subscriptions->count() === 0) {
    echo "⚠️  El usuario NO tiene suscripciones\n";
    echo "   Esto puede causar el error 403\n\n";
} else {
    foreach ($subscriptions as $sub) {
        echo "Suscripción ID: {$sub->id}\n";
        echo "   Plan: {$sub->plan}\n";
        echo "   Company ID: {$sub->company_id}\n";
        echo "   Status: {$sub->status}\n";
        echo "   Inicio: {$sub->starts_at}\n";
        echo "   Expira: {$sub->expires_at}\n";
        echo "   ¿Está activa? " . ($sub->isActive() ? 'SÍ' : 'NO') . "\n";

        // Verificar features
        echo "   Features:\n";
        $features = $sub->features ?? [];
        foreach ($features as $feature => $enabled) {
            $status = $enabled ? '✅' : '❌';
            echo "     {$status} {$feature}\n";
        }
        echo "\n";
    }
}

// Paso 3: Buscar una compañía con quotes
echo "🏢 PASO 3: BUSCAR COMPAÑÍA CON QUOTES\n";
echo str_repeat("─", 60) . "\n";

$companyWithQuotes = \App\Models\Quote::select('company_id')
    ->distinct()
    ->first();

if (!$companyWithQuotes) {
    echo "⚠️  No hay quotes en la base de datos\n";
    echo "   Creando un quote de prueba...\n";

    // Buscar una compañía
    $company = \App\Models\Company::first();
    if (!$company) {
        echo "❌ No hay compañías. Creando una...\n";
        $company = \App\Models\Company::create([
            'user_id' => $userId,
            'rif' => 'J' . rand(10000000, 99999999),
            'email' => 'test_quotes@example.com',
            'name' => 'Test Quotes Company',
            'status' => 'active',
            'key_system_items_id' => 1,
        ]);
        echo "✅ Compañía creada - ID: {$company->id}\n";
    }

    // Crear cliente y vendedor
    $customer = \App\Models\Customer::where('company_id', $company->id)->first();
    if (!$customer) {
        $customer = \App\Models\Customer::create([
            'company_id' => $company->id,
            'code' => 'CUST-TEST',
            'name' => 'Cliente Test',
            'email' => 'cliente@test.com',
            'status' => 'active',
        ]);
    }

    // Crear quote
    $quote = \App\Models\Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'user_seller_id' => $userId,
        'quote_number' => 'W' . str_pad(rand(1, 999999), 9, '0', STR_PAD_LEFT),
        'quote_date' => now(),
        'valid_until' => now()->addDays(30)->toDateString(),
        'subtotal' => 1000,
        'tax_amount' => 160,
        'discount_amount' => 0,
        'total' => 1160,
        'status' => 'draft', // Usar 'draft' en lugar de 'pending'
    ]);

    echo "✅ Quote creado - ID: {$quote->id}\n";
    $companyId = $company->id;

} else {
    $companyId = $companyWithQuotes->company_id;
    echo "✅ Compañía encontrada con quotes - ID: {$companyId}\n";

    $quoteCount = \App\Models\Quote::where('company_id', $companyId)->count();
    echo "   Quotes encontrados: {$quoteCount}\n";
}

echo "\n";

// Paso 4: Verificar suscripción específica para esta compañía
echo "🔍 PASO 4: VERIFICAR SUSCRIPCIÓN PARA LA COMPAÑÍA\n";
echo str_repeat("─", 60) . "\n";

$subscription = \App\Models\Subscription::where('user_id', $userId)
    ->where('company_id', $companyId)
    ->active()
    ->first();

if (!$subscription) {
    echo "❌ NO hay suscripción activa para la compañía {$companyId}\n";
    echo "   Esto causa el error 403\n\n";

    echo "💡 Creando suscripción TRIAL para esta compañía...\n";
    $planConfig = \App\Models\Subscription::getPlanConfig(\App\Models\Subscription::PLAN_TRIAL);

    $subscription = \App\Models\Subscription::create([
        'user_id' => $userId,
        'company_id' => $companyId,
        'plan' => \App\Models\Subscription::PLAN_TRIAL,
        'starts_at' => now(),
        'expires_at' => now()->addDays($planConfig['duration_days']),
        'status' => \App\Models\Subscription::STATUS_ACTIVE,
        'features' => $planConfig['features'],
        'amount' => $planConfig['price'],
        'currency' => 'USD',
    ]);

    echo "✅ Suscripción TRIAL creada\n";
    echo "   Plan: {$subscription->plan}\n";
    echo "   Expira: {$subscription->expires_at}\n";
    echo "   Features:\n";
    foreach ($subscription->features as $feature => $enabled) {
        $status = $enabled ? '✅' : '❌';
        echo "     {$status} {$feature}\n";
    }
    echo "\n";

    // Verificar si tiene sync_quotes
    if (empty($subscription->features['sync_quotes'])) {
        echo "⚠️  El plan TRIAL NO tiene el feature sync_quotes\n";
        echo "   Necesita upgrade a Monthly o superior\n\n";

        echo "💡 Haciendo upgrade a plan MONTHLY para probar...\n";
        $planConfig = \App\Models\Subscription::getPlanConfig(\App\Models\Subscription::PLAN_MONTHLY);

        $subscription->update([
            'plan' => \App\Models\Subscription::PLAN_MONTHLY,
            'features' => $planConfig['features'],
            'expires_at' => now()->addDays($planConfig['duration_days']),
        ]);

        echo "✅ Actualizado a plan MONTHLY\n";
        echo "   Ahora tiene sync_quotes: " . ($subscription->fresh()->features['sync_quotes'] ? 'SÍ' : 'NO') . "\n\n";
    }
} else {
    echo "✅ Suscripción activa encontrada\n";
    echo "   Plan: {$subscription->plan}\n";
    echo "   ¿Tiene sync_quotes? " . ($subscription->hasFeature('sync_quotes') ? 'SÍ ✅' : 'NO ❌') . "\n\n";
}

// Paso 5: Probar el endpoint
echo "📡 PASO 5: PROBAR ENDPOINT GET /api/sync-batch/quotes\n";
echo str_repeat("─", 60) . "\n";

$controller = new \App\Http\Controllers\Api\SyncController();

$request = Request::create('/api/sync-batch/quotes', 'GET', [
    'company_id' => $companyId,
    'status' => 'draft'
]);
$request->headers->set('Content-Type', 'application/json');
$request->setUserResolver(function () use ($user) {
    return $user;
});

echo "Request params:\n";
echo "   company_id: {$companyId}\n";
echo "   status: draft\n\n";

try {
    $response = $controller->getQuotes($request);
    $data = json_decode($response->getContent(), true);

    echo "✅ Status: {$response->getStatusCode()}\n";

    if ($data['success']) {
        echo "✅ Success: true\n";
        echo "   Quotes encontrados: " . count($data['quotes']) . "\n";

        if (count($data['quotes']) > 0) {
            echo "\n   Primer quote:\n";
            $quote = $data['quotes'][0];
            echo "     ID: {$quote['id']}\n";
            echo "     Número: {$quote['quote_number']}\n";
            echo "     Estado: {$quote['status']}\n";
            echo "     Total: {$quote['total']}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

// Paso 6: Prueba con curl (real)
echo "🌐 PASO 6: PRUEBA CON CURL (HTTP REAL)\n";
echo str_repeat("─", 60) . "\n";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "http://localhost/api/sync-batch/quotes?company_id={$companyId}&status=draft",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Host: sales-apiWEB.local",
        "Authorization: Bearer {$token}",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

if ($err) {
    echo "❌ Error cURL: {$err}\n";
} else {
    echo "HTTP Status: {$httpCode}\n";

    if ($httpCode === 403) {
        echo "❌ ERROR 403 - Forbidden\n\n";
        echo "Respuesta del servidor:\n";
        $json = json_decode($response, true);
        if ($json) {
            print_r($json);
        } else {
            echo $response;
        }

        echo "\n\n💡 SOLUCIONES POSIBLES:\n";
        echo "1. Verificar que el usuario tenga suscripción activa\n";
        echo "2. Verificar que la suscripción tenga el feature 'sync_quotes'\n";
        echo "3. Verificar que el token no esté expirado\n";
        echo "4. Verificar que la compañía pertenezca al usuario\n";
    } else if ($httpCode === 200) {
        echo "✅ SUCCESS - HTTP 200\n\n";
        $json = json_decode($response, true);
        if ($json && $json['success']) {
            echo "Quotes encontrados: " . count($json['quotes']) . "\n";
        }
    } else {
        echo "Respuesta:\n";
        echo $response . "\n";
    }
}

echo "\n";
