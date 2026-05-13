<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'admin@test.com')->first();

if ($user) {
    $count = $user->tokens()->delete();
    echo "✅ Sesiones eliminadas: {$count}\n";
} else {
    echo "❌ Usuario no encontrado\n";
}
