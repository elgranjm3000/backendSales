<?php

/**
 * TEST COMPLETO del SyncController
 * Prueba todos los métodos y endpoints del SyncController
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║           TEST COMPLETO: SyncController Todos los Métodos            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Función helper para crear request con usuario autenticado
function createRequest($method, $uri, $data, $user) {
    $request = Request::create($uri, $method, [], [], [], [], json_encode($data));
    $request->headers->set('Content-Type', 'application/json');
    $request->setUserResolver(function () use ($user) {
        return \App\Models\User::find($user['id']);
    });
    return $request;
}

// Función helper para imprimir resultados
function printResult($testName, $response) {
    $data = json_decode($response->getContent(), true);
    $status = $response->getStatusCode();

    echo "📍 {$testName}\n";
    echo "   Status: {$status}\n";

    if ($status >= 200 && $status < 300) {
        echo "   ✅ EXITOSO\n";
        if (isset($data['created'])) echo "   Created: {$data['created']}\n";
        if (isset($data['updated'])) echo "   Updated: {$data['updated']}\n";
        if (isset($data['deleted'])) echo "   Deleted: {$data['deleted']}\n";
        if (isset($data['errors'])) echo "   Errors: {$data['errors']}\n";
    } else {
        echo "   ❌ ERROR\n";
        echo "   Message: " . ($data['message'] ?? 'Unknown error') . "\n";
    }

    if (isset($data['error_details']) && !empty($data['error_details'])) {
        echo "   Error Details:\n";
        foreach (array_slice($data['error_details'], 0, 2) as $error) {
            echo "      - [{$error['index']}] {$error['error']}\n";
        }
    }

    echo "\n";
    return $data;
}

// ============================================================================
// PASO 1: LOGIN
// ============================================================================
echo "📝 PASO 1: LOGIN Y OBTENER TOKEN\n";
echo str_repeat("─", 70) . "\n";

$request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
    'email' => 'admin@test.com',
    'password' => 'password',
    'device_name' => 'sync-test-complete'
]));
$request->headers->set('Content-Type', 'application/json');

try {
    $controller = new \App\Http\Controllers\Api\AuthController();
    $response = $controller->login($request);
    $data = json_decode($response->getContent(), true);

    if (!$data['success']) {
        die("❌ Login falló: " . ($data['message'] ?? 'Unknown error') . "\n");
    }

    $token = $data['data']['token'];
    $user = $data['data']['user'];
    $subscription = $data['data']['subscription'] ?? null;

    echo "✅ Login exitoso\n";
    echo "Usuario: {$user['email']}\n";
    echo "Rol: {$user['role']}\n";
    echo "Plan: " . ($subscription['plan'] ?? 'N/A') . "\n";
    echo "\n";

} catch (\Exception $e) {
    die("❌ Error en login: " . $e->getMessage() . "\n");
}

// Variables para guardar IDs creados
$createdCompanyIds = [];
$createdProductCodes = [];
$createdCustomerDocs = [];
$createdCategoryNames = [];
$createdSellerCodes = [];
$createdQuoteIds = [];

$controller = new \App\Http\Controllers\Api\SyncController();

// ============================================================================
// PASO 2: TEST validateCompany
// ============================================================================
echo "📝 PASO 2: TEST validateCompany\n";
echo str_repeat("─", 70) . "\n\n";

// Test 2.1: Crear nueva empresa
$timestamp = time();
$rif = "J{$timestamp}";

$request = createRequest('POST', '/api/sync-batch/company/validate', [
    'rif' => $rif,
    'email' => "test{$timestamp}@example.com",
    'name' => 'Empresa Test SyncController'
], $user);

try {
    $response = $controller->validateCompany($request);
    $data = printResult("2.1. Crear nueva empresa", $response);
    if ($response->getStatusCode() === 201) {
        $createdCompanyIds[] = $data['company_id'];
    }
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// Test 2.2: Validar empresa existente
$request = createRequest('POST', '/api/sync-batch/company/validate', [
    'rif' => $rif,
    'email' => "test{$timestamp}@example.com"
], $user);

try {
    $response = $controller->validateCompany($request);
    printResult("2.2. Validar empresa existente", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 3: TEST syncProductsBatch
// ============================================================================
echo "📝 PASO 3: TEST syncProductsBatch\n";
echo str_repeat("─", 70) . "\n\n";

// Test 3.1: Crear productos
$testProducts = [];
for ($i = 1; $i <= 5; $i++) {
    $code = "TEST_SYNC_" . $timestamp . "_{$i}";
    $createdProductCodes[] = $code;
    $testProducts[] = [
        'code' => $code,
        'name' => "Producto Test {$i}",
        'description' => "Descripción del producto {$i}",
        'price' => 100 * $i,
        'cost' => 50 * $i,
        'higher_price' => 120 * $i,
        'coin' => 'USD',
        'description_coin' => 'Dólares',
        'stock' => 100,
        'min_stock' => 10,
        'category_id' => 1, // Usar categoría existente
        'status' => 'active',
        'weight' => 1.5,
        'unitary_cost' => 50.0,
        'buy_tax' => '0',
        'buy_aliquot' => 0.0,
        'sale_tax' => '16',
        'aliquot' => 16.0
    ];
}

$request = createRequest('POST', '/api/sync-batch/products', [
    'company_id' => 1,
    'products' => $testProducts
], $user);

try {
    $response = $controller->syncProductsBatch($request);
    printResult("3.1. Crear 5 productos", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// Test 3.2: Actualizar productos existentes
$updatedProducts = [];
foreach ($createdProductCodes as $code) {
    $updatedProducts[] = [
        'code' => $code,
        'name' => 'Producto Actualizado',
        'price' => 999,
        'cost' => 500,
        'higher_price' => 1200,
        'coin' => 'USD',
        'description_coin' => 'Dólares',
        'stock' => 200,
        'min_stock' => 20,
        'category_id' => 1,
        'status' => 'active',
        'weight' => 2.0,
        'unitary_cost' => 500.0,
        'buy_tax' => '0',
        'buy_aliquot' => 0.0,
        'sale_tax' => '16',
        'aliquot' => 16.0
    ];
}

$request = createRequest('POST', '/api/sync-batch/products', [
    'company_id' => 1,
    'products' => $updatedProducts
], $user);

try {
    $response = $controller->syncProductsBatch($request);
    printResult("3.2. Actualizar 5 productos", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// Test 3.3: Intentar enviar más de 5000 productos
$largeBatch = array_fill(0, 5001, ['code' => 'TOO_MANY', 'name' => 'Test']);

$request = createRequest('POST', '/api/sync-batch/products', [
    'company_id' => 1,
    'products' => $largeBatch
], $user);

try {
    $response = $controller->syncProductsBatch($request);
    printResult("3.3. Probar límite de 5000 (debe fallar)", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 4: TEST destroyProductsBatch
// ============================================================================
echo "📝 PASO 4: TEST destroyProductsBatch\n";
echo str_repeat("─", 70) . "\n\n";

// Eliminar solo los primeros 2 productos
$codesToDelete = array_slice($createdProductCodes, 0, 2);

$request = createRequest('DELETE', '/api/sync-batch/products', [
    'company_id' => 1,
    'codes' => $codesToDelete
], $user);

try {
    $response = $controller->destroyProductsBatch($request);
    printResult("4.1. Eliminar 2 productos", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 5: TEST syncCustomersBatch
// ============================================================================
echo "📝 PASO 5: TEST syncCustomersBatch\n";
echo str_repeat("─", 70) . "\n\n";

$testCustomers = [];
for ($i = 1; $i <= 3; $i++) {
    $doc = "V{$timestamp}" . str_pad($i, 4, '0', STR_PAD_LEFT);
    $createdCustomerDocs[] = $doc;
    $testCustomers[] = [
        'document_number' => $doc,
        'name' => "Cliente Test {$i}",
        'email' => "cliente{$i}@test.com",
        'phone' => "555-000{$i}",
        'address' => "Dirección {$i}"
    ];
}

$request = createRequest('POST', '/api/sync-batch/customers', [
    'company_id' => 1,
    'customers' => $testCustomers
], $user);

try {
    $response = $controller->syncCustomersBatch($request);
    printResult("5.1. Crear 3 clientes", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 6: TEST syncCategoriesBatch
// ============================================================================
echo "📝 PASO 6: TEST syncCategoriesBatch\n";
echo str_repeat("─", 70) . "\n\n";

$testCategories = [];
for ($i = 1; $i <= 3; $i++) {
    $name = "Categoría Sync {$timestamp}_{$i}";
    $createdCategoryNames[] = $name;
    $testCategories[] = [
        'name' => $name,
        'description' => "Descripción categoría {$i}",
        'status' => 'active'
    ];
}

$request = createRequest('POST', '/api/sync-batch/categories', [
    'company_id' => 1,
    'categories' => $testCategories
], $user);

try {
    $response = $controller->syncCategoriesBatch($request);
    printResult("6.1. Crear 3 categorías", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 7: TEST syncSellersBatch
// ============================================================================
echo "📝 PASO 7: TEST syncSellersBatch\n";
echo str_repeat("─", 70) . "\n\n";

// Nota: Este test requiere password hasheado, usaremos 'password' hasheado con bcrypt
$hashedPassword = bcrypt('password123');

$testSellers = [];
for ($i = 1; $i <= 2; $i++) {
    $code = "SELLER_{$timestamp}_{$i}";
    $createdSellerCodes[] = $code;
    $testSellers[] = [
        'code' => $code,
        'description' => "Vendedor Test {$i}",
        'email' => "vendedor{$i}{$timestamp}@test.com",
        'password' => $hashedPassword,
        'status' => 'active'
    ];
}

$request = createRequest('POST', '/api/sync-batch/sellers', [
    'company_id' => 1,
    'sellers' => $testSellers
], $user);

try {
    $response = $controller->syncSellersBatch($request);
    printResult("7.1. Crear 2 vendedores", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 8: TEST createQuote
// ============================================================================
echo "📝 PASO 8: TEST createQuote\n";
echo str_repeat("─", 70) . "\n\n";

// Primero obtenemos un customer_id válido
$customer = \App\Models\Customer::where('document_number', $createdCustomerDocs[0])->first();
$productId = \App\Models\Product::where('code', $createdProductCodes[2])->first();

if ($customer && $productId) {
    $quoteData = [
        'company_id' => 1,
        'quote_number' => "QUOTE-{$timestamp}",
        'customer_id' => $customer->id,
        'user_seller_id' => null,
        'subtotal' => 300,
        'tax_amount' => 60,
        'discount' => 0,
        'discount_amount' => 0,
        'total' => 360,
        'bcv_rate' => 35.5,
        'status' => 'draft', // Usar 'draft' en lugar de 'pending'
        'items' => [
            [
                'product_id' => $productId->id,
                'name' => $productId->name,
                'item_type' => 'product',
                'unit' => 'pcs',
                'quantity' => 2,
                'price' => 150,  // El controlador espera 'price', no 'unit_price'
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'tax_percentage' => 16,
                'tax_amount' => 48,
                'buy_tax' => 0,
                'subtotal' => 300,
                'total' => 348,
                'type_price' => 'ST', // Solo 2 caracteres permitidos
                'sort_order' => 1
            ]
        ]
    ];

    $request = createRequest('POST', '/api/sync-batch/quotes', $quoteData, $user);

    try {
        $response = $controller->createQuote($request);
        $data = printResult("8.1. Crear quote", $response);
        if ($response->getStatusCode() === 201) {
            $createdQuoteIds[] = $data['quote_id'];
        }
    } catch (\Exception $e) {
        echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "⚠️  No se puede crear quote - faltan customer o product\n\n";
}

// ============================================================================
// PASO 9: TEST getQuotes
// ============================================================================
echo "📝 PASO 9: TEST getQuotes\n";
echo str_repeat("─", 70) . "\n\n";

$request = createRequest('GET', '/api/sync-batch/quotes?company_id=1', [], $user);

try {
    $response = $controller->getQuotes($request);
    $data = printResult("9.1. Obtener quotes", $response);
    if (isset($data['quotes'])) {
        echo "   Total quotes: " . count($data['quotes']) . "\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 10: TEST updateQuoteStatus
// ============================================================================
echo "📝 PASO 10: TEST updateQuoteStatus\n";
echo str_repeat("─", 70) . "\n\n";

if (!empty($createdQuoteIds)) {
    $quoteId = $createdQuoteIds[0];

    $request = createRequest('PUT', "/api/sync-batch/quotes/{$quoteId}/status", [
        'status' => 'approved',
        'company_id' => 1
    ], $user);

    try {
        $response = $controller->updateQuoteStatus($request, $quoteId);
        printResult("10.1. Actualizar status de quote", $response);
    } catch (\Exception $e) {
        echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "⚠️  No hay quotes para actualizar\n\n";
}

// ============================================================================
// PASO 11: TEST destroyCategoriesBatch
// ============================================================================
echo "📝 PASO 11: TEST destroyCategoriesBatch\n";
echo str_repeat("─", 70) . "\n\n";

$request = createRequest('DELETE', '/api/sync-batch/categories', [
    'company_id' => 1,
    'names' => array_slice($createdCategoryNames, 0, 2)
], $user);

try {
    $response = $controller->destroyCategoriesBatch($request);
    printResult("11.1. Eliminar 2 categorías", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PASO 12: TEST destroyCustomersBatch
// ============================================================================
echo "📝 PASO 12: TEST destroyCustomersBatch\n";
echo str_repeat("─", 70) . "\n\n";

$request = createRequest('DELETE', '/api/sync-batch/customers', [
    'company_id' => 1,
    'documents' => array_slice($createdCustomerDocs, 0, 1)
], $user);

try {
    $response = $controller->destroyCustomersBatch($request);
    printResult("12.1. Eliminar 1 cliente", $response);
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo str_repeat("═", 70) . "\n";
echo "║                         RESUMEN FINAL                             ║\n";
echo str_repeat("═", 70) . "\n";
echo "\n";

echo "📊 ESTADÍSTICAS DE LA PRUEBA:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Empresas creadas: " . count($createdCompanyIds) . "\n";
echo "Productos creados: " . count($createdProductCodes) . "\n";
echo "Clientes creados: " . count($createdCustomerDocs) . "\n";
echo "Categorías creadas: " . count($createdCategoryNames) . "\n";
echo "Vendedores creados: " . count($createdSellerCodes) . "\n";
echo "Quotes creados: " . count($createdQuoteIds) . "\n";
echo "\n";

echo "✅ TEST COMPLETADO\n";
echo "\n";
