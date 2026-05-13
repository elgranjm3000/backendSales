<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   PRUEBA DE LOGIN COMPLETA                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Crear request de login
$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'test-device'
]));
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

// Obtener el controlador
$controller = new \App\Http\Controllers\Api\AuthController();

try {
    echo "📤 ENVIANDO REQUEST:\n";
    echo "   POST /api/auth/login\n";
    echo "   {\n";
    echo "     \"email\": \"admin@test.com\",\n";
    echo "     \"password\": \"password\",\n";
    echo "     \"device_name\": \"test-device\"\n";
    echo "   }\n";
    echo "\n";

    $response = $controller->login($request);
    $content = $response->getContent();
    $data = json_decode($content, true);

    if (!$data['success']) {
        echo "❌ LOGIN FALLÓ: " . ($data['message'] ?? 'Unknown error') . "\n";
        exit(1);
    }

    echo "✅ LOGIN EXITOSO\n";
    echo "\n";

    // Mostrar datos del usuario
    echo "👤 USUARIO:\n";
    echo "   ID: " . $data['data']['user']['id'] . "\n";
    echo "   Nombre: " . $data['data']['user']['name'] . "\n";
    echo "   Email: " . $data['data']['user']['email'] . "\n";
    echo "   Rol: " . $data['data']['user']['role'] . "\n";
    echo "   Status: " . $data['data']['user']['status'] . "\n";
    echo "\n";

    // Mostrar token
    $token = $data['data']['token'];
    echo "🔑 TOKEN:\n";
    echo "   " . substr($token, 0, 50) . "...\n";
    echo "   Expira: " . $data['data']['token_expires_at'] . "\n";
    echo "\n";

    // Mostrar suscripción si existe
    if (isset($data['data']['subscription'])) {
        $sub = $data['data']['subscription'];
        echo "📦 SUSCRIPCIÓN:\n";
        echo "   Plan: " . $sub['plan'] . "\n";
        echo "   Plan Name: " . ($sub['plan_name'] ?? 'N/A') . "\n";
        echo "   Status: " . $sub['status'] . "\n";
        echo "   Expires At: " . $sub['expires_at'] . "\n";
        echo "   Days Remaining: " . $sub['days_remaining'] . "\n";
        echo "\n";

        echo "🔓 FEATURES DISPONIBLES:\n";
        foreach ($sub['features'] as $feature => $enabled) {
            $icon = $enabled ? '✅' : '❌';
            echo sprintf("   %s %s\n", $icon, $feature);
        }
        echo "\n";

        // Mostrar qué puede y qué no puede hacer
        echo "📋 QUÉ PUEDE HACER:\n";
        echo "   ✅ GET/POST /api/products (tiene sync_products)\n";
        echo "   ✅ GET/POST /api/customers (tiene sync_customers)\n";
        echo "   ✅ GET/POST /api/sellers (tiene sync_sellers)\n";
        echo "   ✅ GET/POST /api/categories (tiene sync_categories)\n";
        echo "   ❌ GET/POST /api/quotes (NO tiene sync_quotes)\n";
        echo "   ❌ POST /api/sync-data/quotes (requiere upgrade a Monthly)\n";
        echo "\n";

        // Calcular días restantes
        $daysRemaining = $sub['days_remaining'];
        if ($daysRemaining > 0) {
            echo "⏰ TU SUSCRIPCIÓN EXPIRA EN {$daysRemaining} DÍAS\n";
            echo "   Fecha: " . $sub['expires_at'] . "\n";
        } else {
            echo "⚠️  TU SUSCRIPCIÓN HA EXPIRADO\n";
            echo "   Expiró hace: " . abs($daysRemaining) . " días\n";
        }
        echo "\n";

    } else {
        echo "⚠️  NO TIENE SUSCRIPCIÓN\n";
        echo "   Debes crear una suscripción para acceder a la API\n";
        echo "\n";
    }

    // Mostrar ejemplo de uso
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "💡 EJEMPLO DE USO DEL TOKEN:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    echo "# Obtener productos (✅ PERMITIDO)\n";
    echo "curl -X GET http://localhost/api/products \\\n";
    echo "  -H \"Authorization: Bearer {$token}\"\n";
    echo "\n";
    echo "# Intentar obtener quotes (❌ BLOQUEADO)\n";
    echo "curl -X GET http://localhost/api/quotes \\\n";
    echo "  -H \"Authorization: Bearer {$token}\"\n";
    echo "\n";

    echo "✅ Prueba completada exitosamente\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}
