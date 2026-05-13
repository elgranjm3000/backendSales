<?php

/**
 * Script para cambiar los tipos ENUM a VARCHAR en PostgreSQL
 * Esto permite que Laravel maneje los Enums correctamente
 */

try {
    $pdo = new PDO(
        'pgsql:host=172.19.0.1;port=5432;dbname=chrystal_movil',
        'chrystal_app',
        'muentes123.',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== CAMBIANDO ENUMS A VARCHAR EN POSTGRESQL ===\n\n";

    $pdo->beginTransaction();

    // 1. Cambiar role de users de VARCHAR (ya está bien)
    echo "✓ Columna role de users ya es VARCHAR\n";

    // 2. Cambiar status de users
    echo "Cambiar status de users de status_enum a VARCHAR...\n";
    $pdo->exec("ALTER TABLE users ALTER COLUMN status TYPE VARCHAR(255) USING status::text");
    echo "✓ Columna status de users cambiada a VARCHAR\n\n";

    // 3. Cambiar status de categories
    echo "Cambiar status de categories de category_status_enum a VARCHAR...\n";
    $pdo->exec("ALTER TABLE categories ALTER COLUMN status TYPE VARCHAR(255) USING status::text");
    echo "✓ Columna status de categories cambiada a VARCHAR\n\n";

    // 4. Cambiar status de companies
    echo "Cambiar status de companies de status_enum a VARCHAR...\n";
    $pdo->exec("ALTER TABLE companies ALTER COLUMN status TYPE VARCHAR(255) USING status::text");
    echo "✓ Columna status de companies cambiada a VARCHAR\n\n";

    // 5. Cambiar status de products
    echo "Cambiar status de products de category_status_enum a VARCHAR...\n";
    $pdo->exec("ALTER TABLE products ALTER COLUMN status TYPE VARCHAR(255) USING status::text");
    echo "✓ Columna status de products cambiada a VARCHAR\n\n";

    // 6. Cambiar status de quotes
    echo "Cambiar status de quotes de quote_status_enum a VARCHAR...\n";
    $pdo->exec("ALTER TABLE quotes ALTER COLUMN status TYPE VARCHAR(255) USING status::text");
    echo "✓ Columna status de quotes cambiada a VARCHAR\n\n";

    // 7. Cambiar seller_status de sellers
    echo "Cambiar seller_status de sellers de seller_status_enum a VARCHAR...\n";
    $pdo->exec("ALTER TABLE sellers ALTER COLUMN seller_status TYPE VARCHAR(255) USING seller_status::text");
    echo "✓ Columna seller_status de sellers cambiada a VARCHAR\n\n";

    $pdo->commit();

    echo "\n=== ¡CAMBIOS COMPLETADOS! ===\n";
    echo "\nAhora los Enums serán manejados por Laravel, no por PostgreSQL.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
