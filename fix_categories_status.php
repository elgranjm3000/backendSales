<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Revisando categorías con status inválido...\n\n";

$categories = DB::table('categories')->select('id', 'name', 'status')->get();
$problematic = [];

foreach($categories as $cat) {
    if (!in_array($cat->status, ['active', 'inactive'])) {
        $problematic[] = $cat;
        echo "ID: {$cat->id} | Name: {$cat->name} | Status: {$cat->status}\n";
    }
}

echo "\nTotal con problemas: " . count($problematic) . "\n\n";

if (count($problematic) > 0) {
    echo "Actualizando status inválidos a 'active'...\n";
    $updated = DB::table('categories')
        ->whereNotIn('status', ['active', 'inactive'])
        ->update(['status' => 'active']);
    echo "✅ Registros actualizados: {$updated}\n";
}

echo "\nProbando endpoint de categorías...\n";

try {
    $controller = new \App\Http\Controllers\Api\CategoryController();
    $request = Illuminate\Http\Request::create('/api/categories', 'GET');

    // Set user resolver
    $user = \App\Models\User::where('email', 'admin@test.com')->first();
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);

    if ($response->getStatusCode() === 200) {
        $count = is_array($data['data'] ?? null) ? count($data['data']) : 0;
        echo "✅ Categorías obtenidas correctamente: {$count} registros\n";
    } else {
        echo "❌ Error: Status {$response->getStatusCode()}\n";
        echo "Mensaje: " . ($data['message'] ?? 'Unknown') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}
