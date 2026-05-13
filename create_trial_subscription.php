<?php

/**
 * Script para crear suscripciones de prueba
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdo = DB::connection()->getPdo();
    echo "=== Crear Suscripciones de Prueba ===\n\n";

    // Buscar usuario admin
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute(['admin@test.com']);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminUser) {
        echo "❌ Usuario admin@test.com no encontrado\n";
        echo "Ejecuta primero: php seed_database.php\n";
        exit(1);
    }

    echo "👤 Usuario encontrado: {$adminUser['name']} ({$adminUser['email']})\n";

    // Buscar compañía del admin
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE user_id = ?");
    $stmt->execute([$adminUser['id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo "❌ No se encontró compañía para el usuario admin\n";
        exit(1);
    }

    echo "🏢 Compañía: {$company['name']} (ID: {$company['id']})\n\n";

    // Verificar si ya tiene suscripción
    $stmt = $pdo->prepare("SELECT id, plan, status FROM subscriptions WHERE user_id = ? AND company_id = ?");
    $stmt->execute([$adminUser['id'], $company['id']]);
    $existingSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSubscription) {
        echo "⚠️  El usuario ya tiene una suscripción:\n";
        echo "   ID: {$existingSubscription['id']}\n";
        echo "   Plan: {$existingSubscription['plan']}\n";
        echo "   Status: {$existingSubscription['status']}\n";

        echo "\n¿Deseas eliminarla y crear una nueva? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 's') {
            echo "Cancelado.\n";
            exit(0);
        }

        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->execute([$existingSubscription['id']]);
        echo "✅ Suscripción anterior eliminada\n\n";
    }

    // Crear suscripción TRIAL
    $plan = 'trial';
    $planConfig = \App\Models\Subscription::getPlanConfig($plan);

    $stmt = $pdo->prepare("
        INSERT INTO subscriptions (
            user_id, company_id, plan, starts_at, expires_at,
            status, features, amount, currency, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        RETURNING id
    ");

    $startsAt = now()->toDateTimeString();
    $expiresAt = now()->addDays($planConfig['duration_days'])->toDateTimeString();
    $featuresJson = json_encode($planConfig['features']);

    $stmt->execute([
        $adminUser['id'],
        $company['id'],
        $plan,
        $startsAt,
        $expiresAt,
        'active',
        $featuresJson,
        $planConfig['price'],
        'USD'
    ]);

    $subscriptionId = $stmt->fetchColumn();

    echo "✅ Suscripción TRIAL creada exitosamente!\n\n";
    echo "📋 Detalles:\n";
    echo "   ID: {$subscriptionId}\n";
    echo "   Plan: {$planConfig['name']}\n";
    echo "   Duración: {$planConfig['duration_days']} días\n";
    echo "   Precio: \${$planConfig['price']} USD\n";
    echo "   Inicio: {$startsAt}\n";
    echo "   Expira: {$expiresAt}\n\n";

    echo "🔓 Features incluidas:\n";
    foreach ($planConfig['features'] as $feature => $enabled) {
        $status = $enabled ? '✅' : '❌';
        echo "   {$status} {$feature}\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "\n📌 Para probar el sistema:\n\n";
    echo "1. Haz login para obtener el token:\n";
    echo "   curl -X POST http://localhost/api/auth/login \\\n";
    echo "     -H 'Content-Type: application/json' \\\n";
    echo "     -d '{\"email\": \"admin@test.com\", \"password\": \"password\"}'\n\n";

    echo "2. El token expirará el: {$expiresAt}\n";
    echo "   (coincide con la expiración de la suscripción)\n\n";

    echo "3. Intenta sincronizar productos (disponible en trial):\n";
    echo "   curl -X POST http://localhost/api/sync-data/products \\\n";
    echo "     -H 'Authorization: Bearer TU_TOKEN' \\\n";
    echo "     -H 'Content-Type: application/json' \\\n";
    echo "     -d '{\"company_id\": {$company['id']}, \"products\": [...]}'\n\n";

    echo "4. Intenta sincronizar quotes (NO disponible en trial):\n";
    echo "   Debería retornar error 403 con mensaje de feature no disponible\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
