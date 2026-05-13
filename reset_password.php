<?php

/**
 * Script para resetear la contraseña del usuario admin@test.com
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $email = 'admin@test.com';
    $newPassword = 'password'; // Nueva contraseña
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);

    $pdo = DB::connection()->getPdo();

    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ Usuario NO encontrado: $email\n";
        exit(1);
    }

    echo "👤 Usuario encontrado:\n";
    echo "   ID: {$user['id']}\n";
    echo "   Nombre: {$user['name']}\n";
    echo "   Email: {$user['email']}\n\n";

    // Actualizar contraseña
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
    $stmt->execute([$hash, $email]);

    echo "✅ Contraseña actualizada exitosamente!\n\n";
    echo "🔐 Nuevas credenciales:\n";
    echo "   Email: $email\n";
    echo "   Password: $newPassword\n\n";

    // Verificar el hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $storedHash = $stmt->fetchColumn();

    if (password_verify($newPassword, $storedHash)) {
        echo "✅ Verificación de contraseña exitosa!\n";
    } else {
        echo "❌ Error en la verificación de contraseña\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
