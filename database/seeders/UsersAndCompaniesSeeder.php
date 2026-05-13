<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersAndCompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Crear categorías básicas primero
        $categories = [
            ['name' => 'Alimentos', 'description' => 'Productos alimentarios', 'status' => 'active'],
            ['name' => 'Bebidas', 'description' => 'Bebidas de todo tipo', 'status' => 'active'],
            ['name' => 'Servicios', 'description' => 'Servicios diversos', 'status' => 'active'],
            ['name' => 'Productos', 'description' => 'Productos generales', 'status' => 'active'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'description' => $category['description'],
                'status' => $category['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Crear key_system_items (necesario para companies)
        $keySystemItems = [
            ['id' => 1, 'name' => 'key1', 'description' => 'Sistema para restaurantes'],
            ['id' => 2, 'name' => 'key2', 'description' => 'Sistema para retail'],
            ['id' => 3, 'name' => 'key3', 'description' => 'Sistema para servicios'],
        ];

        foreach ($keySystemItems as $item) {
            DB::table('key_system_items')->insertOrIgnore([
                'id' => $item['id'],
                'key_activation' => $item['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // USUARIOS COMPANY Y SUS EMPRESAS
        $companies = [
            [
                'user' => [
                    'name' => 'Carlos Restaurant Manager',
                    'email' => 'carlos@restaurant.com',
                    'phone' => '+58412-1234567',
                    'role' => 'company',
                    'password' => 'password123'
                ],
                'company' => [
                    'name' => 'Restaurant El Buen Sabor',
                    'rif' => 'J-30123456-7',
                    'description' => 'Restaurante especializado en comida tradicional venezolana',
                    'address' => 'Av. Francisco de Miranda, Centro Comercial Plaza',
                    'country' => 'Venezuela',
                    'province' => 'Distrito Capital',
                    'city' => 'Caracas',
                    'phone' => '+58212-5551234',
                    'email' => 'info@buensabor.com',
                    'contact' => 'Carlos Rodríguez',
                    'key_system_items_id' => 1,
                    'serial_no' => 'REST-001'
                ]
            ],
            [
                'user' => [
                    'name' => 'María Retail Owner',
                    'email' => 'maria@tienda.com',
                    'phone' => '+58424-9876543',
                    'role' => 'company',
                    'password' => 'password123'
                ],
                'company' => [
                    'name' => 'Tienda La Esquina',
                    'rif' => 'J-30987654-3',
                    'description' => 'Tienda de abarrotes y productos de primera necesidad',
                    'address' => 'Calle Principal con Avenida Bolívar',
                    'country' => 'Venezuela',
                    'province' => 'Miranda',
                    'city' => 'Los Teques',
                    'phone' => '+58212-9998888',
                    'email' => 'contacto@laesquina.com',
                    'contact' => 'María González',
                    'key_system_items_id' => 2,
                    'serial_no' => 'RETAIL-002'
                ]
            ],
            [
                'user' => [
                    'name' => 'José Service Manager',
                    'email' => 'jose@servicios.com',
                    'phone' => '+58416-5555555',
                    'role' => 'company',
                    'password' => 'password123'
                ],
                'company' => [
                    'name' => 'Servicios Integrales JM',
                    'rif' => 'J-31555666-9',
                    'description' => 'Empresa de servicios de mantenimiento y reparaciones',
                    'address' => 'Zona Industrial La Urbina',
                    'country' => 'Venezuela',
                    'province' => 'Distrito Capital',
                    'city' => 'Caracas',
                    'phone' => '+58212-7777777',
                    'email' => 'servicios@integralesjm.com',
                    'contact' => 'José Martínez',
                    'key_system_items_id' => 3,
                    'serial_no' => 'SERV-003'
                ]
            ]
        ];

        $companyIds = [];

        foreach ($companies as $companyData) {
            // Crear usuario company
            $userId = DB::table('users')->insertGetId([
                'name' => $companyData['user']['name'],
                'email' => $companyData['user']['email'],
                'phone' => $companyData['user']['phone'],
                'role' => $companyData['user']['role'],
                'status' => 'active',
                'email_verified_at' => $now,
                'password' => Hash::make($companyData['user']['password']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Crear company
            $companyId = DB::table('companies')->insertGetId([
                'user_id' => $userId,
                'name' => $companyData['company']['name'],
                'rif' => $companyData['company']['rif'],
                'description' => $companyData['company']['description'],
                'address' => $companyData['company']['address'],
                'country' => $companyData['company']['country'],
                'province' => $companyData['company']['province'],
                'city' => $companyData['company']['city'],
                'phone' => $companyData['company']['phone'],
                'email' => $companyData['company']['email'],
                'contact' => $companyData['company']['contact'],
                'key_system_items_id' => $companyData['company']['key_system_items_id'],
                'serial_no' => $companyData['company']['serial_no'],
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $companyIds[] = $companyId;
        }

        // USUARIOS SELLER
        $sellers = [
            [
                'name' => 'Ana López',
                'email' => 'ana.lopez@seller.com',
                'phone' => '+58414-1111111',
                'role' => 'seller',
                'password' => 'password123'
            ],
            [
                'name' => 'Pedro Ramírez',
                'email' => 'pedro.ramirez@seller.com',
                'phone' => '+58424-2222222',
                'role' => 'seller',
                'password' => 'password123'
            ],
            [
                'name' => 'Luis Hernández',
                'email' => 'luis.hernandez@seller.com',
                'phone' => '+58416-3333333',
                'role' => 'seller',
                'password' => 'password123'
            ],
            [
                'name' => 'Carmen Díaz',
                'email' => 'carmen.diaz@seller.com',
                'phone' => '+58426-4444444',
                'role' => 'seller',
                'password' => 'password123'
            ],
            [
                'name' => 'Roberto Silva',
                'email' => 'roberto.silva@seller.com',
                'phone' => '+58412-5555555',
                'role' => 'seller',
                'password' => 'password123'
            ],
            [
                'name' => 'Elena Morales',
                'email' => 'elena.morales@seller.com',
                'phone' => '+58414-6666666',
                'role' => 'seller',
                'password' => 'password123'
            ]
        ];

        $sellerIds = [];

        foreach ($sellers as $sellerData) {
            $sellerId = DB::table('users')->insertGetId([
                'name' => $sellerData['name'],
                'email' => $sellerData['email'],
                'phone' => $sellerData['phone'],
                'role' => $sellerData['role'],
                'status' => 'active',
                'email_verified_at' => $now,
                'password' => Hash::make($sellerData['password']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sellerIds[] = $sellerId;
        }

        // CREAR REGISTROS EN TABLA SELLERS (asignar vendedores a empresas)
        $sellerAssignments = [
            // Restaurant El Buen Sabor (company_id = 1)
            [
                'user_id' => $sellerIds[0], // Ana López
                'company_id' => $companyIds[0],
                'code' => 'SELL-REST-001',
                'description' => 'Vendedora principal del restaurante',
                'percent_sales' => 5.5,
                'percent_receivable' => 3.0,
                'inkeeper' => true,
                'user_code' => 'AL001',
                'percent_gerencial_debit_note' => 2.5,
                'percent_gerencial_credit_note' => 2.0,
                'percent_returned_check' => 1.5,
                'seller_status' => 'active'
            ],
            [
                'user_id' => $sellerIds[1], // Pedro Ramírez
                'company_id' => $companyIds[0],
                'code' => 'SELL-REST-002',
                'description' => 'Vendedor de mesa y delivery',
                'percent_sales' => 4.0,
                'percent_receivable' => 2.5,
                'inkeeper' => false,
                'user_code' => 'PR002',
                'percent_gerencial_debit_note' => 2.0,
                'percent_gerencial_credit_note' => 1.5,
                'percent_returned_check' => 1.0,
                'seller_status' => 'active'
            ],
            // Tienda La Esquina (company_id = 2)
            [
                'user_id' => $sellerIds[2], // Luis Hernández
                'company_id' => $companyIds[1],
                'code' => 'SELL-TIENDA-001',
                'description' => 'Vendedor principal de tienda',
                'percent_sales' => 6.0,
                'percent_receivable' => 3.5,
                'inkeeper' => true,
                'user_code' => 'LH001',
                'percent_gerencial_debit_note' => 3.0,
                'percent_gerencial_credit_note' => 2.5,
                'percent_returned_check' => 2.0,
                'seller_status' => 'active'
            ],
            [
                'user_id' => $sellerIds[3], // Carmen Díaz
                'company_id' => $companyIds[1],
                'code' => 'SELL-TIENDA-002',
                'description' => 'Vendedora de mostrador',
                'percent_sales' => 4.5,
                'percent_receivable' => 2.8,
                'inkeeper' => false,
                'user_code' => 'CD002',
                'percent_gerencial_debit_note' => 2.2,
                'percent_gerencial_credit_note' => 1.8,
                'percent_returned_check' => 1.2,
                'seller_status' => 'active'
            ],
            // Servicios Integrales JM (company_id = 3)
            [
                'user_id' => $sellerIds[4], // Roberto Silva
                'company_id' => $companyIds[2],
                'code' => 'SELL-SERV-001',
                'description' => 'Vendedor técnico especializado',
                'percent_sales' => 7.0,
                'percent_receivable' => 4.0,
                'inkeeper' => true,
                'user_code' => 'RS001',
                'percent_gerencial_debit_note' => 3.5,
                'percent_gerencial_credit_note' => 3.0,
                'percent_returned_check' => 2.5,
                'seller_status' => 'active'
            ],
            [
                'user_id' => $sellerIds[5], // Elena Morales
                'company_id' => $companyIds[2],
                'code' => 'SELL-SERV-002',
                'description' => 'Vendedora de servicios comerciales',
                'percent_sales' => 5.5,
                'percent_receivable' => 3.2,
                'inkeeper' => false,
                'user_code' => 'EM002',
                'percent_gerencial_debit_note' => 2.8,
                'percent_gerencial_credit_note' => 2.3,
                'percent_returned_check' => 1.8,
                'seller_status' => 'active'
            ]
        ];

        foreach ($sellerAssignments as $assignment) {
            DB::table('sellers')->insert(array_merge($assignment, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // CREAR ALGUNOS CLIENTES DE EJEMPLO
        $customers = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@cliente.com',
                'phone' => '+58412-7777777',
                'document_type' => 'V',
                'document_number' => '12345678',
                'address' => 'Urbanización Los Palos Grandes',
                'city' => 'Caracas',
                'state' => 'Distrito Capital',
                'zip_code' => '1060',
                'status' => 'active'
            ],
            [
                'name' => 'María Rodríguez',
                'email' => 'maria.rodriguez@cliente.com',
                'phone' => '+58424-8888888',
                'document_type' => 'V',
                'document_number' => '87654321',
                'address' => 'Av. Universidad, Edificio Torre',
                'city' => 'Caracas',
                'state' => 'Distrito Capital',
                'zip_code' => '1040',
                'status' => 'active'
            ],
            [
                'name' => 'Empresa ABC C.A.',
                'email' => 'contacto@empresaabc.com',
                'phone' => '+58212-1111111',
                'document_type' => 'J',
                'document_number' => '40123456-7',
                'address' => 'Zona Industrial Los Ruices',
                'city' => 'Caracas',
                'state' => 'Distrito Capital',
                'zip_code' => '1071',
                'status' => 'active'
            ]
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->insert(array_merge($customer, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // CREAR ALGUNOS PRODUCTOS DE EJEMPLO
        $products = [
            [
                'name' => 'Arepa Reina Pepiada',
                'code' => 'ARE-REINA-001',
                'description' => 'Arepa con pollo desmenuzado y aguacate',
                'price' => 3.50,
                'cost' => 2.20,
                'stock' => 100,
                'min_stock' => 20,
                'category_id' => 1,
                'barcode' => '7501234567890',
                'weight' => 0.250,
                'status' => 'active'
            ],
            [
                'name' => 'Jugo Natural de Naranja',
                'code' => 'JUG-NAR-001',
                'description' => 'Jugo natural de naranja recién exprimida',
                'price' => 2.25,
                'cost' => 1.50,
                'stock' => 50,
                'min_stock' => 15,
                'category_id' => 2,
                'barcode' => '7501234567891',
                'weight' => 0.300,
                'status' => 'active'
            ],
            [
                'name' => 'Pasta Carbonara',
                'code' => 'PAS-CAR-001',
                'description' => 'Pasta italiana con salsa carbonara',
                'price' => 8.75,
                'cost' => 5.50,
                'stock' => 30,
                'min_stock' => 10,
                'category_id' => 1,
                'barcode' => '7501234567892',
                'weight' => 0.400,
                'status' => 'active'
            ],
            [
                'name' => 'Servicio de Reparación AC',
                'code' => 'SERV-AC-001',
                'description' => 'Servicio de reparación y mantenimiento de aire acondicionado',
                'price' => 45.00,
                'cost' => 25.00,
                'stock' => 999,
                'min_stock' => 1,
                'category_id' => 3,
                'weight' => 0.000,
                'status' => 'active'
            ]
        ];

        foreach ($products as $product) {
            DB::table('products')->insert(array_merge($product, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // CREAR COTIZACIONES DE EJEMPLO
        $quotes = [
            [
                'quote_number' => 'COT-2025-001',
                'customer_id' => 1, // Juan Pérez
                'company_id' => $companyIds[0], // Restaurant El Buen Sabor
                'subtotal' => 14.50,
                'tax' => 2.61, // 18% IGV
                'discount' => 1.00,
                'total' => 16.11,
                'status' => 'draft',
                'notes' => 'Cotización para evento familiar',
                'terms_conditions' => 'Precios válidos por 30 días. Delivery disponible en Caracas.',
                'quote_date' => $now->format('Y-m-d H:i:s'),
                'valid_until' => $now->copy()->addDays(30)->format('Y-m-d'),
                'metadata' => json_encode([
                    'source' => 'web',
                    'priority' => 'normal',
                    'delivery_required' => true
                ])
            ],
            [
                'quote_number' => 'COT-2025-002',
                'customer_id' => 2, // María Rodríguez
                'company_id' => $companyIds[1], // Tienda La Esquina
                'subtotal' => 25.75,
                'tax' => 4.64, // 18% IGV
                'discount' => 2.50,
                'total' => 27.89,
                'status' => 'sent',
                'notes' => 'Cotización para compra mensual',
                'terms_conditions' => 'Descuentos por volumen aplicables. Crédito disponible.',
                'quote_date' => $now->copy()->subDays(3)->format('Y-m-d H:i:s'),
                'valid_until' => $now->copy()->addDays(15)->format('Y-m-d'),
                'sent_at' => $now->copy()->subDays(2)->format('Y-m-d H:i:s'),
                'metadata' => json_encode([
                    'source' => 'phone',
                    'priority' => 'high',
                    'customer_type' => 'regular'
                ])
            ],
            [
                'quote_number' => 'COT-2025-003',
                'customer_id' => 3, // Empresa ABC C.A.
                'company_id' => $companyIds[2], // Servicios Integrales JM
                'subtotal' => 180.00,
                'tax' => 32.40, // 18% IGV
                'discount' => 15.00,
                'total' => 197.40,
                'status' => 'approved',
                'notes' => 'Servicio de mantenimiento trimestral para oficinas',
                'terms_conditions' => 'Servicio incluye garantía de 90 días. Horario de 8am a 5pm.',
                'quote_date' => $now->copy()->subDays(7)->format('Y-m-d H:i:s'),
                'valid_until' => $now->copy()->addDays(45)->format('Y-m-d'),
                'sent_at' => $now->copy()->subDays(6)->format('Y-m-d H:i:s'),
                'approved_at' => $now->copy()->subDays(1)->format('Y-m-d H:i:s'),
                'metadata' => json_encode([
                    'source' => 'visit',
                    'priority' => 'high',
                    'contract_type' => 'maintenance'
                ])
            ],
            [
                'quote_number' => 'COT-2025-004',
                'customer_id' => 1, // Juan Pérez
                'company_id' => $companyIds[0], // Restaurant El Buen Sabor
                'subtotal' => 42.25,
                'tax' => 7.61, // 18% IGV
                'discount' => 5.00,
                'total' => 44.86,
                'status' => 'expired',
                'notes' => 'Cotización para celebración de cumpleaños',
                'terms_conditions' => 'Precios especiales para eventos. Reserva requerida.',
                'quote_date' => $now->copy()->subDays(45)->format('Y-m-d H:i:s'),
                'valid_until' => $now->copy()->subDays(10)->format('Y-m-d'),
                'sent_at' => $now->copy()->subDays(44)->format('Y-m-d H:i:s'),
                'metadata' => json_encode([
                    'source' => 'web',
                    'priority' => 'normal',
                    'event_type' => 'birthday'
                ])
            ]
        ];

        $quoteIds = [];
        foreach ($quotes as $quote) {
            $quoteId = DB::table('quotes')->insertGetId(array_merge($quote, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            $quoteIds[] = $quoteId;
        }

        // CREAR ITEMS PARA LAS COTIZACIONES
        $quoteItems = [
            // Items para COT-2025-001 (Restaurant)
            [
                'quote_id' => $quoteIds[0],
                'item_type' => 'product',
                'product_id' => 1, // Arepa Reina Pepiada
                'name' => 'Arepa Reina Pepiada',
                'description' => 'Arepa con pollo desmenuzado y aguacate',
                'unit' => 'pcs',
                'quantity' => 3.000,
                'unit_price' => 3.50,
                'discount_percentage' => 0.00,
                'discount_amount' => 0.00,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 10.50,
                'total' => 10.50,
                'sort_order' => 1
            ],
            [
                'quote_id' => $quoteIds[0],
                'item_type' => 'product',
                'product_id' => 2, // Jugo Natural de Naranja
                'name' => 'Jugo Natural de Naranja',
                'description' => 'Jugo natural de naranja recién exprimida',
                'unit' => 'pcs',
                'quantity' => 2.000,
                'unit_price' => 2.25,
                'discount_percentage' => 10.00,
                'discount_amount' => 0.45,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 4.50,
                'total' => 4.00,
                'sort_order' => 2
            ],

            // Items para COT-2025-002 (Tienda)
            [
                'quote_id' => $quoteIds[1],
                'item_type' => 'product',
                'product_id' => 1, // Arepa Reina Pepiada
                'name' => 'Arepa Reina Pepiada',
                'description' => 'Arepa con pollo desmenuzado y aguacate - Combo familiar',
                'unit' => 'pcs',
                'quantity' => 5.000,
                'unit_price' => 3.50,
                'discount_percentage' => 5.00,
                'discount_amount' => 0.88,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 17.50,
                'total' => 16.62,
                'sort_order' => 1
            ],
            [
                'quote_id' => $quoteIds[1],
                'item_type' => 'product',
                'product_id' => 2, // Jugo Natural de Naranja
                'name' => 'Jugo Natural de Naranja',
                'description' => 'Jugo natural de naranja recién exprimida - Pack familiar',
                'unit' => 'pcs',
                'quantity' => 4.000,
                'unit_price' => 2.25,
                'discount_percentage' => 0.00,
                'discount_amount' => 0.00,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 9.00,
                'total' => 9.00,
                'sort_order' => 2
            ],

            // Items para COT-2025-003 (Servicios)
            [
                'quote_id' => $quoteIds[2],
                'item_type' => 'product',
                'product_id' => 4, // Servicio de Reparación AC
                'name' => 'Servicio de Mantenimiento AC',
                'description' => 'Mantenimiento preventivo y correctivo de sistema de aire acondicionado',
                'unit' => 'hrs',
                'quantity' => 4.000,
                'unit_price' => 45.00,
                'discount_percentage' => 8.33,
                'discount_amount' => 15.00,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 180.00,
                'total' => 165.00,
                'sort_order' => 1
            ],

            // Items para COT-2025-004 (Restaurant - Expirada)
            [
                'quote_id' => $quoteIds[3],
                'item_type' => 'product',
                'product_id' => 1, // Arepa Reina Pepiada
                'name' => 'Arepa Reina Pepiada',
                'description' => 'Arepa con pollo desmenuzado y aguacate',
                'unit' => 'pcs',
                'quantity' => 8.000,
                'unit_price' => 3.50,
                'discount_percentage' => 0.00,
                'discount_amount' => 0.00,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 28.00,
                'total' => 28.00,
                'sort_order' => 1
            ],
            [
                'quote_id' => $quoteIds[3],
                'item_type' => 'product',
                'product_id' => 3, // Pasta Carbonara
                'name' => 'Pasta Carbonara',
                'description' => 'Pasta italiana con salsa carbonara',
                'unit' => 'pcs',
                'quantity' => 2.000,
                'unit_price' => 8.75,
                'discount_percentage' => 14.29,
                'discount_amount' => 2.50,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 17.50,
                'total' => 15.00,
                'sort_order' => 2
            ],
            [
                'quote_id' => $quoteIds[3],
                'item_type' => 'product',
                'product_id' => 2, // Jugo Natural de Naranja
                'name' => 'Jugo Natural de Naranja',
                'description' => 'Jugo natural de naranja recién exprimida',
                'unit' => 'pcs',
                'quantity' => 3.000,
                'unit_price' => 2.25,
                'discount_percentage' => 0.00,
                'discount_amount' => 0.00,
                'tax_percentage' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => 6.75,
                'total' => 6.75,
                'sort_order' => 3
            ]
        ];

        foreach ($quoteItems as $item) {
            DB::table('quote_items')->insert(array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->info('Seeder completado:');
        $this->command->info('- 3 usuarios tipo "company" con sus empresas');
        $this->command->info('- 6 usuarios tipo "seller" asignados a las empresas');
        $this->command->info('- 3 clientes de ejemplo');
        $this->command->info('- 4 productos de ejemplo');
        $this->command->info('- 4 categorías básicas');
        $this->command->info('- 4 cotizaciones con diferentes estados');
        $this->command->info('- 8 items de cotización distribuidos');
        $this->command->info('');
        $this->command->info('Credenciales de acceso (password: password123):');
        $this->command->info('Companies:');
        $this->command->info('- carlos@restaurant.com (Restaurant El Buen Sabor)');
        $this->command->info('- maria@tienda.com (Tienda La Esquina)');
        $this->command->info('- jose@servicios.com (Servicios Integrales JM)');
        $this->command->info('');
        $this->command->info('Sellers:');
        $this->command->info('- ana.lopez@seller.com');
        $this->command->info('- pedro.ramirez@seller.com');
        $this->command->info('- luis.hernandez@seller.com');
        $this->command->info('- carmen.diaz@seller.com');
        $this->command->info('- roberto.silva@seller.com');
        $this->command->info('- elena.morales@seller.com');
        $this->command->info('');
        $this->command->info('Cotizaciones creadas:');
        $this->command->info('- COT-2025-001: Borrador ($16.11)');
        $this->command->info('- COT-2025-002: Enviada ($27.89)');
        $this->command->info('- COT-2025-003: Aprobada ($197.40)');
        $this->command->info('- COT-2025-004: Expirada ($44.86)');
    }
}