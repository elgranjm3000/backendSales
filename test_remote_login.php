<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "=== TEST LOGIN: admin@test.com / password ===\n\n";

// Crear request de login
$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'remote-test'
]));
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

try {
    echo "📤 ENVIANDO REQUEST...\n";

    $controller = new \App\Http\Controllers\Api\AuthController();
    $response = $controller->login($request);

    echo "📊 STATUS CODE: " . $response->getStatusCode() . "\n\n";

    $content = $response->getContent();
    $data = json_decode($content, true);

    if ($data['success'] ?? false) {
        echo "✅ LOGIN EXITOSO\n\n";
        echo "🔑 Token: " . substr($data['data']['token'], 0, 60) . "...\n";
        echo "👤 Usuario: " . $data['data']['user']['email'] . "\n";
        echo "👤 Rol: " . $data['data']['user']['role'] . "\n";

        if (isset($data['data']['subscription'])) {
            $sub = $data['data']['subscription'];
            echo "\n📦 SUSCRIPCIÓN:\n";
            echo "   Plan: " . $sub['plan'] . "\n";
            echo "   Status: " . $sub['status'] . "\n";
            echo "   Días restantes: " . $sub['days_remaining'] . "\n";
        } else {
            echo "\n⚠️  SIN SUSCRIPCIÓN\n";
        }
    } else {
        echo "❌ LOGIN FALLÓ\n";
        echo "Mensaje: " . ($data['message'] ?? 'Unknown error') . "\n";

        if (isset($data['errors'])) {
            echo "Errors: " . json_encode($data['errors'], JSON_PRETTY_PRINT) . "\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ EXCEPCIÓN CAPTURADA:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";

    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
