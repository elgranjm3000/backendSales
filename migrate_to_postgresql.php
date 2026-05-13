<?php

/**
 * Script para migrar la estructura de MySQL a PostgreSQL
 * Conecta a PostgreSQL y crea las tablas necesarias
 */

try {
    // Conexión a PostgreSQL
    $pdo = new PDO(
        'pgsql:host=172.19.0.1;port=5432;dbname=chrystal_movil',
        'chrystal_app',
        'muentes123.',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Conectado a PostgreSQL exitosamente!\n";

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar tablas existentes si existen (en orden correcto por foreign keys)
    $tablesToDrop = [
        'quote_items',
        'quotes',
        'sellers',
        'products',
        'customers',
        'categories',
        'companies',
        'system_logs',
        'users',
        'sessions',
        'password_reset_tokens',
        'migrations',
        'key_system_items',
        'job_batches',
        'jobs',
        'failed_jobs',
        'cache_locks',
        'cache',
        'acceso'
    ];

    foreach ($tablesToDrop as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table CASCADE");
        echo "Eliminada tabla (si existía): $table\n";
    }

    // Crear enum para status
    $pdo->exec("DROP TYPE IF EXISTS status_enum CASCADE");
    $pdo->exec("CREATE TYPE status_enum AS ENUM ('active', 'inactive')");
    echo "Creado tipo status_enum\n";

    $pdo->exec("DROP TYPE IF EXISTS category_status_enum CASCADE");
    $pdo->exec("CREATE TYPE category_status_enum AS ENUM ('active', 'inactive')");
    echo "Creado tipo category_status_enum\n";

    $pdo->exec("DROP TYPE IF EXISTS quote_status_enum CASCADE");
    $pdo->exec("CREATE TYPE quote_status_enum AS ENUM ('draft', 'sent', 'approved', 'rejected', 'expired')");
    echo "Creado tipo quote_status_enum\n";

    $pdo->exec("DROP TYPE IF EXISTS role_enum CASCADE");
    $pdo->exec("CREATE TYPE role_enum AS ENUM ('admin', 'manager', 'company', 'seller')");
    echo "Creado tipo role_enum\n";

    $pdo->exec("DROP TYPE IF EXISTS seller_status_enum CASCADE");
    $pdo->exec("CREATE TYPE seller_status_enum AS ENUM ('active', 'inactive')");
    echo "Creado tipo seller_status_enum\n";

    // ==================== TABLA users ====================
    $sql = "CREATE TABLE users (
        id BIGSERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(255),
        role role_enum NOT NULL DEFAULT 'seller',
        status status_enum NOT NULL DEFAULT 'active',
        avatar VARCHAR(255),
        email_verified_at TIMESTAMP,
        password VARCHAR(255) NOT NULL,
        remember_token VARCHAR(100),
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: users\n";

    // ==================== TABLA key_system_items ====================
    $sql = "CREATE TABLE key_system_items (
        id BIGSERIAL PRIMARY KEY,
        key_activation VARCHAR(255),
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: key_system_items\n";

    // ==================== TABLA companies ====================
    $sql = "CREATE TABLE companies (
        id BIGSERIAL PRIMARY KEY,
        user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        rif VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        address VARCHAR(255),
        country VARCHAR(255),
        province VARCHAR(255),
        city VARCHAR(255),
        phone VARCHAR(255),
        logo BYTEA,
        logo_type VARCHAR(255),
        email VARCHAR(255) NOT NULL DEFAULT '00',
        contact VARCHAR(255),
        key_system_items_id BIGINT NOT NULL REFERENCES key_system_items(id),
        serial_no VARCHAR(255),
        restaurant_image BYTEA,
        restaurant_image_type VARCHAR(255),
        main_image BYTEA,
        main_image_type VARCHAR(255),
        status status_enum NOT NULL DEFAULT 'active',
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: companies\n";

    // ==================== TABLA categories ====================
    $sql = "CREATE TABLE categories (
        id BIGSERIAL PRIMARY KEY,
        company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        status category_status_enum NOT NULL DEFAULT 'active',
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: categories\n";

    // ==================== TABLA customers ====================
    $sql = "CREATE TABLE customers (
        id BIGSERIAL PRIMARY KEY,
        company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
        name VARCHAR(255),
        email VARCHAR(255) NOT NULL,
        contact VARCHAR(255),
        phone VARCHAR(255),
        document_type VARCHAR(255),
        document_number VARCHAR(255),
        address VARCHAR(255),
        city VARCHAR(255),
        state VARCHAR(255),
        zip_code VARCHAR(255),
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        status VARCHAR(255) NOT NULL DEFAULT 'active',
        additional_info JSONB,
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: customers\n";

    // ==================== TABLA products ====================
    $sql = "CREATE TABLE products (
        id BIGSERIAL PRIMARY KEY,
        product_type VARCHAR(255),
        company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
        cost DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
        higher_price DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
        coin VARCHAR(60) NOT NULL,
        description_coin VARCHAR(60) NOT NULL,
        unidad TEXT DEFAULT 'N/A',
        stock DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
        min_stock DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
        image VARCHAR(255),
        images JSONB,
        category_id BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
        status category_status_enum NOT NULL DEFAULT 'active',
        barcode VARCHAR(255),
        weight DECIMAL(10,3) NOT NULL DEFAULT 0.000,
        attributes JSONB,
        unitary_cost REAL NOT NULL,
        buy_tax TEXT NOT NULL,
        buy_aliquot REAL NOT NULL,
        sale_tax TEXT NOT NULL,
        aliquot REAL NOT NULL,
        allow_decimal SMALLINT,
        created_at TIMESTAMP,
        updated_at TIMESTAMP,
        UNIQUE(company_id, code)
    )";
    $pdo->exec($sql);
    echo "Creada tabla: products\n";

    // ==================== TABLA sellers ====================
    $sql = "CREATE TABLE sellers (
        id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
        code VARCHAR(255) NOT NULL,
        description TEXT,
        status VARCHAR(255),
        percent_sales DOUBLE PRECISION NOT NULL DEFAULT 0,
        percent_receivable DOUBLE PRECISION NOT NULL DEFAULT 0,
        inkeeper SMALLINT NOT NULL DEFAULT 0,
        user_code VARCHAR(255),
        percent_gerencial_debit_note DOUBLE PRECISION NOT NULL DEFAULT 0,
        percent_gerencial_credit_note DOUBLE PRECISION NOT NULL DEFAULT 0,
        percent_returned_check DOUBLE PRECISION NOT NULL DEFAULT 0,
        seller_status seller_status_enum NOT NULL DEFAULT 'active',
        created_at TIMESTAMP,
        updated_at TIMESTAMP,
        UNIQUE(user_id, company_id),
        UNIQUE(company_id, code)
    )";
    $pdo->exec($sql);
    echo "Creada tabla: sellers\n";

    // ==================== TABLA quotes ====================
    $sql = "CREATE TABLE quotes (
        id BIGSERIAL PRIMARY KEY,
        quote_number VARCHAR(255) NOT NULL,
        customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
        company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
        user_seller_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
        subtotal DECIMAL(15,6) NOT NULL DEFAULT 0.000000,
        tax DECIMAL(15,6) NOT NULL DEFAULT 0.000000,
        tax_amount DECIMAL(15,6) NOT NULL DEFAULT 0.000000,
        discount DECIMAL(15,6) NOT NULL DEFAULT 0.000000,
        discount_amount DECIMAL(15,6) NOT NULL DEFAULT 0.000000,
        total DECIMAL(15,6) NOT NULL DEFAULT 0.000000,
        bcv_rate DECIMAL(10,6),
        bcv_date DATE,
        status quote_status_enum NOT NULL DEFAULT 'rejected',
        notes TEXT,
        terms_conditions TEXT,
        quote_date TIMESTAMP NOT NULL DEFAULT '2025-08-29 09:52:29',
        valid_until DATE NOT NULL,
        sent_at TIMESTAMP,
        approved_at TIMESTAMP,
        metadata JSONB,
        created_at TIMESTAMP,
        updated_at TIMESTAMP,
        UNIQUE(quote_number, company_id)
    )";
    $pdo->exec($sql);
    echo "Creada tabla: quotes\n";

    // ==================== TABLA quote_items ====================
    $sql = "CREATE TABLE quote_items (
        id BIGSERIAL PRIMARY KEY,
        quote_id BIGINT NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
        item_type VARCHAR(255) NOT NULL DEFAULT 'product',
        product_id BIGINT REFERENCES products(id) ON DELETE SET NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        unit VARCHAR(255) NOT NULL DEFAULT 'pcs',
        quantity DECIMAL(10,3) NOT NULL DEFAULT 1.000,
        unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        tax_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        buy_tax INTEGER NOT NULL,
        subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        type_price VARCHAR(2) NOT NULL,
        sort_order INTEGER NOT NULL DEFAULT 0,
        metadata JSONB,
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: quote_items\n";

    // ==================== TABLA acceso ====================
    $sql = "CREATE TABLE acceso (
        id BIGSERIAL PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL UNIQUE,
        nombre VARCHAR(255) NOT NULL,
        id_fiscal VARCHAR(50) NOT NULL,
        direccion TEXT,
        telefono VARCHAR(20),
        zona VARCHAR(100),
        ciudad VARCHAR(100),
        grupo VARCHAR(150),
        vendedor VARCHAR(200),
        contacto VARCHAR(200),
        estado VARCHAR(100),
        correo_electronico VARCHAR(255),
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: acceso\n";

    // ==================== TABLA cache ====================
    $sql = "CREATE TABLE cache (
        key VARCHAR(255) PRIMARY KEY,
        value TEXT NOT NULL,
        expiration INTEGER NOT NULL
    )";
    $pdo->exec($sql);
    echo "Creada tabla: cache\n";

    // ==================== TABLA cache_locks ====================
    $sql = "CREATE TABLE cache_locks (
        key VARCHAR(255) PRIMARY KEY,
        owner VARCHAR(255) NOT NULL,
        expiration INTEGER NOT NULL
    )";
    $pdo->exec($sql);
    echo "Creada tabla: cache_locks\n";

    // ==================== TABLA failed_jobs ====================
    $sql = "CREATE TABLE failed_jobs (
        id BIGSERIAL PRIMARY KEY,
        uuid VARCHAR(255) NOT NULL UNIQUE,
        connection TEXT NOT NULL,
        queue TEXT NOT NULL,
        payload TEXT NOT NULL,
        exception TEXT NOT NULL,
        failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: failed_jobs\n";

    // ==================== TABLA jobs ====================
    $sql = "CREATE TABLE jobs (
        id BIGSERIAL PRIMARY KEY,
        queue VARCHAR(255) NOT NULL,
        payload TEXT NOT NULL,
        attempts SMALLINT NOT NULL,
        reserved_at INTEGER,
        available_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    )";
    $pdo->exec($sql);
    echo "Creada tabla: jobs\n";

    // ==================== TABLA job_batches ====================
    $sql = "CREATE TABLE job_batches (
        id VARCHAR(255) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        total_jobs INTEGER NOT NULL,
        pending_jobs INTEGER NOT NULL,
        failed_jobs INTEGER NOT NULL,
        failed_job_ids TEXT NOT NULL,
        options TEXT,
        cancelled_at INTEGER,
        created_at INTEGER NOT NULL,
        finished_at INTEGER
    )";
    $pdo->exec($sql);
    echo "Creada tabla: job_batches\n";

    // ==================== TABLA migrations ====================
    $sql = "CREATE TABLE migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        batch INTEGER NOT NULL
    )";
    $pdo->exec($sql);
    echo "Creada tabla: migrations\n";

    // ==================== TABLA password_reset_tokens ====================
    $sql = "CREATE TABLE password_reset_tokens (
        email VARCHAR(255) PRIMARY KEY,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: password_reset_tokens\n";

    // ==================== TABLA sessions ====================
    $sql = "CREATE TABLE sessions (
        id VARCHAR(255) PRIMARY KEY,
        user_id BIGINT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        payload TEXT NOT NULL,
        last_activity INTEGER NOT NULL
    )";
    $pdo->exec($sql);
    echo "Creada tabla: sessions\n";

    // ==================== TABLA system_logs ====================
    $sql = "CREATE TABLE system_logs (
        id SERIAL PRIMARY KEY,
        user_id VARCHAR(20),
        action VARCHAR(255) NOT NULL,
        record_key TEXT NOT NULL,
        ip_address BYTEA,
        mac_address TEXT,
        location POINT,
        lat DECIMAL(10,8),
        lng DECIMAL(11,8),
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Creada tabla: system_logs\n";

    // Crear índices
    echo "\n--- Creando índices ---\n";

    // users
    $pdo->exec("CREATE INDEX users_email_index ON users(email)");
    echo "Índice creado: users_email_index\n";

    // companies
    $pdo->exec("CREATE INDEX companies_user_id_index ON companies(user_id)");
    $pdo->exec("CREATE INDEX companies_user_id_status_index ON companies(user_id, status)");
    echo "Índices creados para companies\n";

    // categories
    $pdo->exec("CREATE INDEX categories_company_id_index ON categories(company_id)");
    echo "Índice creado: categories_company_id_index\n";

    // customers
    $pdo->exec("CREATE INDEX customers_company_id_index ON customers(company_id)");
    $pdo->exec("CREATE INDEX customers_email_index ON customers(email)");
    $pdo->exec("CREATE INDEX customers_company_id_email_index ON customers(company_id, email)");
    echo "Índices creados para customers\n";

    // products
    $pdo->exec("CREATE INDEX products_category_id_index ON products(category_id)");
    $pdo->exec("CREATE INDEX products_company_id_index ON products(company_id)");
    echo "Índices creados para products\n";

    // quotes
    $pdo->exec("CREATE INDEX quotes_customer_id_status_index ON quotes(customer_id, status)");
    $pdo->exec("CREATE INDEX quotes_status_valid_until_index ON quotes(status, valid_until)");
    $pdo->exec("CREATE INDEX quotes_quote_date_index ON quotes(quote_date)");
    $pdo->exec("CREATE INDEX quotes_valid_until_index ON quotes(valid_until)");
    $pdo->exec("CREATE INDEX quotes_company_id_index ON quotes(company_id)");
    $pdo->exec("CREATE INDEX quotes_user_seller_id_index ON quotes(user_seller_id)");
    echo "Índices creados para quotes\n";

    // quote_items
    $pdo->exec("CREATE INDEX quote_items_quote_id_sort_order_index ON quote_items(quote_id, sort_order)");
    $pdo->exec("CREATE INDEX quote_items_product_id_index ON quote_items(product_id)");
    echo "Índices creados para quote_items\n";

    // sellers
    $pdo->exec("CREATE INDEX sellers_user_id_index ON sellers(user_id)");
    $pdo->exec("CREATE INDEX sellers_company_id_index ON sellers(company_id)");
    $pdo->exec("CREATE INDEX sellers_company_id_seller_status_index ON sellers(company_id, seller_status)");
    $pdo->exec("CREATE INDEX sellers_code_index ON sellers(code)");
    echo "Índices creados para sellers\n";

    // sessions
    $pdo->exec("CREATE INDEX sessions_user_id_index ON sessions(user_id)");
    $pdo->exec("CREATE INDEX sessions_last_activity_index ON sessions(last_activity)");
    echo "Índices creados para sessions\n";

    // jobs
    $pdo->exec("CREATE INDEX jobs_queue_index ON jobs(queue)");
    echo "Índice creado: jobs_queue_index\n";

    // acceso
    $pdo->exec("CREATE INDEX acceso_codigo_index ON acceso(codigo)");
    $pdo->exec("CREATE INDEX acceso_id_fiscal_index ON acceso(id_fiscal)");
    $pdo->exec("CREATE INDEX acceso_zona_index ON acceso(zona)");
    $pdo->exec("CREATE INDEX acceso_ciudad_index ON acceso(ciudad)");
    $pdo->exec("CREATE INDEX acceso_estado_index ON acceso(estado)");
    $pdo->exec("CREATE INDEX acceso_grupo_index ON acceso(grupo)");
    $pdo->exec("CREATE INDEX acceso_vendedor_index ON acceso(vendedor)");
    echo "Índices creados para acceso\n";

    // Commit de la transacción
    $pdo->commit();

    echo "\n=== ¡MIGRACIÓN COMPLETADA EXITOSAMENTE! ===\n";
    echo "Todas las tablas han sido creadas en PostgreSQL.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
