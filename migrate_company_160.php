<?php

/**
 * Script para migrar la compañía ID 160 desde MySQL remoto a PostgreSQL local
 */

echo "=== MIGRACIÓN DE COMPAÑÍA ID 160 ===\n\n";

// Conectar a MySQL remoto
$mysqli = new mysqli('91.238.160.176', 'chrystal_app', 'muentes123.', 'chrystal_movil');

if ($mysqli->connect_error) {
    echo "Error de conexión MySQL: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✓ Conectado a MySQL remoto\n";

// Conectar a PostgreSQL local
$pdo = new PDO(
    'pgsql:host=172.19.0.1;port=5432;dbname=chrystal_movil',
    'chrystal_app',
    'muentes123.',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "✓ Conectado a PostgreSQL local\n\n";

try {
    $pdo->beginTransaction();

    // 1. Obtener y migrar el usuario 595
    echo "--- MIGRANDO USER 595 ---\n";
    $result = $mysqli->query("SELECT * FROM users WHERE id = 595");
    $user = $result->fetch_assoc();

    // Verificar si existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = 595");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "  Usuario ya existe, actualizando...\n";
        $sql = "UPDATE users SET name = ?, email = ?, password = ?, role = ?, status = ? WHERE id = 595";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user['name'],
            $user['email'],
            $user['password'],
            $user['role'],
            $user['status']
        ]);
    } else {
        echo "  Insertando nuevo usuario...\n";
        $sql = "INSERT INTO users (id, name, email, password, role, status, phone, avatar, email_verified_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user['id'],
            $user['name'],
            $user['email'],
            $user['password'],
            $user['role'],
            $user['status'] ?? 'active',
            $user['phone'] ?? null,
            $user['avatar'] ?? null,
            $user['email_verified_at'] ?? null,
            $user['created_at'],
            $user['updated_at']
        ]);
    }
    echo "  ✓ Usuario {$user['name']} (ID: {$user['id']}) migrado\n\n";

    // 2. Obtener y migrar key_system_items 1
    echo "--- MIGRANDO KEY_SYSTEM_ITEM 1 ---\n";
    $result = $mysqli->query("SELECT * FROM key_system_items WHERE id = 1");
    $keyItem = $result->fetch_assoc();

    $stmt = $pdo->prepare("SELECT id FROM key_system_items WHERE id = 1");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "  Key system item ya existe, actualizando...\n";
        $sql = "UPDATE key_system_items SET key_activation = ? WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$keyItem['key_activation']]);
    } else {
        echo "  Insertando nuevo key system item...\n";
        $sql = "INSERT INTO key_system_items (id, key_activation, created_at, updated_at)
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $keyItem['id'],
            $keyItem['key_activation'],
            $keyItem['created_at'],
            $keyItem['updated_at']
        ]);
    }
    echo "  ✓ Key system item (ID: 1) migrado\n\n";

    // 3. Migrar la compañía 160
    echo "--- MIGRANDO COMPAÑÍA 160 ---\n";
    $result = $mysqli->query("SELECT * FROM companies WHERE id = 160");
    $company = $result->fetch_assoc();

    $stmt = $pdo->prepare("SELECT id FROM companies WHERE id = 160");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "  Compañía ya existe, actualizando...\n";
        $sql = "UPDATE companies SET user_id = ?, name = ?, rif = ?, address = ?, phone = ?, email = ?, key_system_items_id = ?, status = ?, updated_at = ? WHERE id = 160";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $company['user_id'],
            $company['name'],
            $company['rif'],
            $company['address'],
            $company['phone'],
            $company['email'],
            $company['key_system_items_id'],
            $company['status'],
            $company['updated_at']
        ]);
    } else {
        echo "  Insertando nueva compañía...\n";
        $sql = "INSERT INTO companies (id, user_id, name, rif, description, address, country, province, city, phone, email, contact, key_system_items_id, serial_no, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $company['id'],
            $company['user_id'],
            $company['name'],
            $company['rif'],
            $company['description'] ?? null,
            $company['address'] ?? null,
            $company['country'] ?? null,
            $company['province'] ?? null,
            $company['city'] ?? null,
            $company['phone'] ?? null,
            $company['email'],
            $company['contact'] ?? null,
            $company['key_system_items_id'],
            $company['serial_no'] ?? null,
            $company['status'],
            $company['created_at'],
            $company['updated_at']
        ]);
    }
    echo "  ✓ Compañía {$company['name']} (ID: {$company['id']}) migrada\n\n";

    // 4. Migrar los vendedores de esta compañía
    echo "--- MIGRANDO VENDEDORES ---\n";
    $result = $mysqli->query("SELECT * FROM sellers WHERE company_id = 160");

    while ($seller = $result->fetch_assoc()) {
        echo "  Procesando vendedor ID {$seller['id']}...\n";

        // Primero migrar el usuario del vendedor si existe
        $userResult = $mysqli->query("SELECT * FROM users WHERE id = {$seller['user_id']}");
        $sellerUser = $userResult->fetch_assoc();

        if ($sellerUser) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$sellerUser['id']]);
            if (!$stmt->fetch()) {
                $sql = "INSERT INTO users (id, name, email, password, role, status, phone, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $sellerUser['id'],
                    $sellerUser['name'],
                    $sellerUser['email'],
                    $sellerUser['password'],
                    $sellerUser['role'],
                    $sellerUser['status'] ?? 'active',
                    $sellerUser['phone'] ?? null,
                    $sellerUser['created_at'],
                    $sellerUser['updated_at']
                ]);
                echo "    ✓ Usuario {$sellerUser['name']} (ID: {$sellerUser['id']}) migrado\n";
            }
        }

        // Migrar el vendedor
        $stmt = $pdo->prepare("SELECT id FROM sellers WHERE id = ?");
        $stmt->execute([$seller['id']]);
        if (!$stmt->fetch()) {
            $sql = "INSERT INTO sellers (id, user_id, company_id, code, description, status, percent_sales, percent_receivable, inkeeper, user_code, percent_gerencial_debit_note, percent_gerencial_credit_note, percent_returned_check, seller_status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $seller['id'],
                $seller['user_id'],
                $seller['company_id'],
                $seller['code'],
                $seller['description'] ?? null,
                $seller['status'] ?? null,
                $seller['percent_sales'] ?? 0,
                $seller['percent_receivable'] ?? 0,
                $seller['inkeeper'] ?? 0,
                $seller['user_code'] ?? null,
                $seller['percent_gerencial_debit_note'] ?? 0,
                $seller['percent_gerencial_credit_note'] ?? 0,
                $seller['percent_returned_check'] ?? 0,
                $seller['seller_status'] ?? 'active',
                $seller['created_at'],
                $seller['updated_at']
            ]);
            echo "    ✓ Vendedor {$seller['code']} (ID: {$seller['id']}) migrado\n";
        }
    }

    $pdo->commit();

    echo "\n=== ¡MIGRACIÓN COMPLETADA! ===\n";
    echo "\nResumen:\n";
    echo "  - Usuario ID 595: {$user['name']}\n";
    echo "  - Key System Item ID 1\n";
    echo "  - Compañía ID 160: {$company['name']}\n";
    echo "  - Vendedores migrados\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
