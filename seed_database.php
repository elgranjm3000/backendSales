<?php

/**
 * Script para crear datos de prueba en la base de datos PostgreSQL
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

try {
    $pdo = DB::connection()->getPdo();
    echo "Conectado a PostgreSQL\n";

    // Limpiar datos existentes
    echo "\n--- Limpiando datos existentes ---\n";
    $pdo->exec("DELETE FROM quote_items");
    $pdo->exec("DELETE FROM quotes");
    $pdo->exec("DELETE FROM sellers");
    $pdo->exec("DELETE FROM products");
    $pdo->exec("DELETE FROM customers");
    $pdo->exec("DELETE FROM categories");
    $pdo->exec("DELETE FROM companies");
    $pdo->exec("DELETE FROM key_system_items");
    $pdo->exec("DELETE FROM users");
    $pdo->exec("DELETE FROM personal_access_tokens");
    echo "Datos limpiados\n";

    // Reiniciar sequences
    echo "\n--- Reiniciando secuencias ---\n";
    $sequences = [
        'users_id_seq',
        'key_system_items_id_seq',
        'companies_id_seq',
        'categories_id_seq',
        'customers_id_seq',
        'products_id_seq',
        'sellers_id_seq',
        'quotes_id_seq',
        'quote_items_id_seq'
    ];
    foreach ($sequences as $seq) {
        try {
            $pdo->exec("SELECT setval('$seq', 1, false)");
        } catch (Exception $e) {
            // La secuencia puede no existir aún
        }
    }
    echo "Secuencias reiniciadas\n";

    // Insertar datos de prueba
    echo "\n--- Insertando datos de prueba ---\n";

    // 1. Insertar usuario admin
    $sql = "INSERT INTO users (id, name, email, password, role, status)
            VALUES (1, 'Administrador', 'admin@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $userId = $stmt->fetchColumn();
    echo "Usuario admin creado (ID: $userId)\n";

    // 2. Insertar key_system_item
    $sql = "INSERT INTO key_system_items (id, key_activation)
            VALUES (1, 'TEST-KEY-12345')
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $keySystemId = $stmt->fetchColumn();
    echo "Key system item creado (ID: $keySystemId)\n";

    // 3. Insertar compañía
    $sql = "INSERT INTO companies (id, user_id, key_system_items_id, name, rif, email, city, country, status)
            VALUES (1, $userId, $keySystemId, 'Empresa Test C.A.', 'J-123456789', 'contact@test.com', 'Caracas', 'Venezuela', 'active')
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $companyId = $stmt->fetchColumn();
    echo "Compañía creada (ID: $companyId)\n";

    // 4. Insertar vendedor (usuario)
    $sql = "INSERT INTO users (id, name, email, password, role, status)
            VALUES (2, 'Vendedor Juan', 'vendedor@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'active')
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sellerUserId = $stmt->fetchColumn();
    echo "Usuario vendedor creado (ID: $sellerUserId)\n";

    // 5. Insertar vendedor (relación)
    $sql = "INSERT INTO sellers (user_id, company_id, code, description, percent_sales, seller_status)
            VALUES ($sellerUserId, $companyId, 'V001', 'Vendedor principal', 10.0, 'active')
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sellerId = $stmt->fetchColumn();
    echo "Vendedor creado (ID: $sellerId)\n";

    // 6. Insertar categorías
    $categories = [
        ['Electrónica', 'Productos electrónicos'],
        ['Ropa', 'Productos de vestir'],
        ['Alimentos', 'Productos alimenticios'],
        ['Herramientas', 'Herramientas varias']
    ];

    $categoryIds = [];
    foreach ($categories as $index => $cat) {
        $sql = "INSERT INTO categories (company_id, name, description, status)
                VALUES ($companyId, :name, :desc, 'active')
                RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $cat[0], 'desc' => $cat[1]]);
        $categoryId = $stmt->fetchColumn();
        $categoryIds[] = $categoryId;
        echo "Categoría creada: {$cat[0]} (ID: $categoryId)\n";
    }

    // 7. Insertar clientes
    $customers = [
        ['Cliente Corporativo', 'cliente1@corp.com', 'Caracas', 'active'],
        ['Juan Pérez', 'juan@gmail.com', 'Valencia', 'active'],
        ['María González', 'maria@hotmail.com', 'Maracay', 'active']
    ];

    $customerIds = [];
    foreach ($customers as $cust) {
        $sql = "INSERT INTO customers (company_id, name, email, city, status)
                VALUES ($companyId, :name, :email, :city, :status)
                RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $cust[0], 'email' => $cust[1], 'city' => $cust[2], 'status' => $cust[3]]);
        $customerId = $stmt->fetchColumn();
        $customerIds[] = $customerId;
        echo "Cliente creado: {$cust[0]} (ID: $customerId)\n";
    }

    // 8. Insertar productos
    $products = [
        ['Laptop HP', 'LAPTOP-001', 'Laptop HP 15.6"', 1500.00, 1200.00, 'USD', 'Dólares', $categoryIds[0]],
        ['Mouse Inalámbrico', 'MOUSE-001', 'Mouse Logitech', 25.00, 15.00, 'USD', 'Dólares', $categoryIds[0]],
        ['Camisa Polo', 'CAMISA-001', 'Camisa polo azul', 35.00, 20.00, 'USD', 'Dólares', $categoryIds[1]],
        ['Taladro Eléctrico', 'TALADRO-001', 'Taladro 500W', 85.00, 60.00, 'USD', 'Dólares', $categoryIds[3]]
    ];

    $productIds = [];
    foreach ($products as $prod) {
        $cost = $prod[4];
        $sql = "INSERT INTO products (company_id, name, code, description, price, cost, coin, description_coin, category_id, status, stock, min_stock, unitary_cost, buy_tax, buy_aliquot, sale_tax, aliquot)
                VALUES ($companyId, :name, :code, :desc, :price, :cost, :coin, :desc_coin, :cat_id, 'active', 100, 10, $cost, 'IVA 16%', 16.0, 'IVA 16%', 16.0)
                RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $prod[0],
            'code' => $prod[1],
            'desc' => $prod[2],
            'price' => $prod[3],
            'cost' => $prod[4],
            'coin' => $prod[5],
            'desc_coin' => $prod[6],
            'cat_id' => $prod[7]
        ]);
        $productId = $stmt->fetchColumn();
        $productIds[] = $productId;
        echo "Producto creado: {$prod[0]} (ID: $productId)\n";
    }

    // 9. Insertar cotización de prueba
    $sql = "INSERT INTO quotes (quote_number, customer_id, company_id, user_seller_id, subtotal, tax, tax_amount, discount, discount_amount, total, status, quote_date, valid_until)
            VALUES ('QT-001', {$customerIds[0]}, $companyId, $sellerUserId, 1525.00, 16.0, 244.00, 0.0, 0.0, 1769.00, 'draft', NOW(), NOW() + INTERVAL '15 days')
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $quoteId = $stmt->fetchColumn();
    echo "Cotización creada: QT-001 (ID: $quoteId)\n";

    // 10. Insertar items de la cotización
    $sql = "INSERT INTO quote_items (quote_id, product_id, name, unit, quantity, unit_price, tax_percentage, tax_amount, subtotal, total, buy_tax, type_price)
            VALUES ($quoteId, {$productIds[0]}, 'Laptop HP', 'pcs', 1, 1500.00, 16.0, 240.00, 1500.00, 1740.00, 0, '01')";
    $pdo->exec($sql);
    echo "Item de cotización creado: Laptop HP\n";

    $sql = "INSERT INTO quote_items (quote_id, product_id, name, unit, quantity, unit_price, tax_percentage, tax_amount, subtotal, total, buy_tax, type_price)
            VALUES ($quoteId, {$productIds[1]}, 'Mouse Inalámbrico', 'pcs', 1, 25.00, 16.0, 4.00, 25.00, 29.00, 0, '01')";
    $pdo->exec($sql);
    echo "Item de cotización creado: Mouse Inalámbrico\n";

    echo "\n=== ¡DATOS DE PRUEBA CREADOS EXITOSAMENTE! ===\n";
    echo "\nCredenciales de prueba:\n";
    echo "  Admin: admin@test.com / password\n";
    echo "  Vendedor: vendedor@test.com / password\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
