<?php

/**
 * Demostración del uso del token en diferentes endpoints
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           DEMOSTRACIÓN: USO DE TOKEN EN ENDPOINTS              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// 1. Login y obtener token
echo "📝 PASO 1: HACER LOGIN Y OBTENER TOKEN\n";
echo str_repeat("-", 60) . "\n";

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'demo-script'
]));
$request->headers->set('Content-Type', 'application/json');

$controller = new \App\Http\Controllers\Api\AuthController();
$response = $controller->login($request);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    die("❌ Login falló: " . $data['message'] . "\n");
}

$token = $data['data']['token'];
$subscription = $data['data']['subscription'] ?? null;

echo "✅ Login exitoso\n";
echo "Token: " . substr($token, 0, 60) . "...\n";
echo "Plan: " . ($subscription['plan'] ?? 'N/A') . "\n";
echo "Días restantes: " . ($subscription['days_remaining'] ?? 'N/A') . "\n";
echo "\n";

// 2. Definir función para hacer requests con token
$makeRequest = function($method, $endpoint, $data = []) use ($token) {
    $url = "http://localhost{$endpoint}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        "Content-Type: application/json",
        "Host: sales-apiWEB.local"
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
};

// 3. Probar diferentes endpoints
$tests = [
    [
        'name' => 'GET /api/products',
        'method' => 'GET',
        'endpoint' => '/api/products',
        'feature' => 'sync_products',
        'expected' => 200
    ],
    [
        'name' => 'GET /api/customers',
        'method' => 'GET',
        'endpoint' => '/api/customers',
        'feature' => 'sync_customers',
        'expected' => 200
    ],
    [
        'name' => 'GET /api/sellers',
        'method' => 'GET',
        'endpoint' => '/api/sellers',
        'feature' => 'sync_sellers',
        'expected' => 200
    ],
    [
        'name' => 'GET /api/quotes',
        'method' => 'GET',
        'endpoint' => '/api/quotes',
        'feature' => 'sync_quotes',
        'expected' => 403
    ],
];

echo "📡 PASO 2: PROBAR ENDPOINTS CON EL TOKEN\n";
echo str_repeat("-", 60) . "\n\n";

foreach ($tests as $test) {
    echo "📍 {$test['name']}\n";
    echo "   Feature requerida: {$test['feature']}\n";
    echo "   Status esperado: {$test['expected']}\n";

    $result = $makeRequest($test['method'], $test['endpoint']);
    $status = $result['status'];
    $body = $result['body'];

    echo "   Status obtenido: {$status}\n";

    if ($status === 200) {
        $count = is_array($body['data'] ?? null) ? count($body['data']) : 0;
        echo "   ✅ EXITOSO - {$count} registros encontrados\n";
    } elseif ($status === 403) {
        echo "   ❌ BLOQUEADO - {$body['message']}\n";
        if (isset($body['data']['current_plan'])) {
            echo "   Plan actual: {$body['data']['current_plan']}\n";
            echo "   Plan requerido: {$body['data']['required_plan']}\n";
        }
    } else {
        echo "   ⚠️  Status inesperado: {$status}\n";
    }

    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 EJEMPLO DE USO DEL TOKEN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
echo "# En curl:\n";
echo 'curl -X GET http://localhost/api/products \\' . "\n";
echo '  -H "Authorization: Bearer ' . $token . '" \\' . "\n";
echo '  -H "Host: sales-apiWEB.local"' . "\n";
echo "\n";
echo "# En Python:\n";
echo "import requests\n";
echo "headers = {'Authorization': 'Bearer {$token}'}\n";
echo "response = requests.get('http://localhost/api/products', headers=headers)\n";
echo "\n";
echo "# En JavaScript:\n";
echo "fetch('http://localhost/api/products', {\n";
echo "  headers: {'Authorization': 'Bearer {$token}'}\n";
echo "})\n";
echo "\n";

echo "✅ Demostración completada\n";
