<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SyncDataController extends Controller
{
    /**
     * Recibir y sincronizar productos desde Python.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'products' => 'required|array|max:5000',
            'products.*.code' => 'required|string|max:50',
            'products.*.name' => 'nullable|string|max:255',
            'products.*.description' => 'nullable|string|max:500',
            'products.*.price' => 'nullable|numeric',
            'products.*.cost' => 'nullable|numeric',
            'products.*.stock' => 'nullable|numeric',
            'products.*.min_stock' => 'nullable|numeric',
            'products.*.category_id' => 'nullable|integer',
            'products.*.status' => 'nullable|string|max:20',
            'products.*.product_type' => 'nullable|string|max:50',
            'products.*.images' => 'nullable',  // Acepta string, array o null
            'products.*.higher_price' => 'nullable|numeric',
            'products.*.sale_tax' => 'nullable|integer',
            'products.*.aliquot' => 'nullable|numeric',
            'products.*.coin' => 'nullable|string|max:10',
            'products.*.description_coin' => 'nullable|string|max:50',
            'products.*.unitary_cost' => 'nullable|numeric',
            'products.*.buy_tax' => 'nullable|integer',
            'products.*.buy_aliquot' => 'nullable|numeric',
            'products.*.unidad' => 'nullable|string|max:50',
            'products.*.allow_decimal' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $products = $request->input('products');

        try {
            $pg = DB::connection('pgsql');

            // Verificar conexión
            $pg->select('SELECT 1');

            // Crear categoría por defecto si no existe para esta empresa
            $defaultCategory = $pg->table('categories')
                ->where('company_id', $companyId)
                ->where('name', 'General')
                ->first();

            if (!$defaultCategory) {
                $defaultCategoryId = $pg->table('categories')->insertGetId([
                    'company_id' => $companyId,
                    'name' => 'General',
                    'description' => 'Categoría por defecto',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id');
            } else {
                $defaultCategoryId = $defaultCategory->id;
            }

            $insertedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            // Iniciar transacción
            $pg->beginTransaction();

            foreach ($products as $product) {
                try {
                    $exists = $pg->table('products')
                        ->where('code', $product['code'])
                        ->first();

                    // Convertir images de string JSON a array si es necesario
                    $images = $product['images'] ?? null;
                    if (is_string($images)) {
                        $decoded = json_decode($images, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $images = $decoded;
                        }
                    }

                    // Convertir images a JSON string para PostgreSQL JSONB
                    $imagesJson = null;
                    if (is_array($images)) {
                        $imagesJson = json_encode($images);
                    } elseif (is_string($images)) {
                        $imagesJson = $images;
                    }

                    $data = [
                        'company_id' => $companyId,
                        'name' => $product['name'] ?? null,
                        'description' => $product['description'] ?? null,
                        'price' => $product['price'] ?? null,
                        'cost' => $product['cost'] ?? null,
                        'stock' => $product['stock'] ?? 0,
                        'min_stock' => $product['min_stock'] ?? 0,
                        'category_id' => $product['category_id'] ?? $defaultCategoryId,
                        'status' => $product['status'] ?? 'active',
                        'product_type' => $product['product_type'] ?? null,
                        'images' => $imagesJson,
                        'higher_price' => $product['higher_price'] ?? null,
                        'sale_tax' => $product['sale_tax'] ?? null,
                        'aliquot' => $product['aliquot'] ?? 0,
                        'coin' => $product['coin'] ?? null,
                        'description_coin' => $product['description_coin'] ?? null,
                        'unitary_cost' => $product['unitary_cost'] ?? null,
                        'buy_tax' => $product['buy_tax'] ?? null,
                        'buy_aliquot' => $product['buy_aliquot'] ?? 0,
                        'unidad' => $product['unidad'] ?? null,
                        'allow_decimal' => $product['allow_decimal'] ?? false,
                        'updated_at' => now(),
                    ];

                    if ($exists) {
                        $pg->table('products')
                            ->where('code', $product['code'])
                            ->update($data);
                        $updatedCount++;
                    } else {
                        $data['code'] = $product['code'];
                        $data['created_at'] = now();
                        $pg->table('products')->insert($data);
                        $insertedCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'code' => $product['code'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pg->commit();

            Log::info('Products synced from Python', [
                'company_id' => $companyId,
                'total' => count($products),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Products synchronized successfully',
                'data' => [
                    'total_processed' => count($products),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            if (isset($pg) && $pg->getConnectionLevel() !== null) {
                $pg->rollBack();
            }

            Log::error('Error syncing products from Python', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error synchronizing products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recibir y sincronizar customers desde Python.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncCustomers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'customers' => 'required|array|max:5000',
            'customers.*.code' => 'required|string|max:50',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.email' => 'nullable|email|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'customers.*.address' => 'nullable|string|max:500',
            'customers.*.status' => 'nullable|string|max:20',
            'customers.*.document_type' => 'nullable|string|max:20',
            'customers.*.document_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $customers = $request->input('customers');

        try {
            $pg = DB::connection('pgsql');
            $pg->select('SELECT 1');

            $insertedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            $pg->beginTransaction();

            foreach ($customers as $customer) {
                try {
                    $exists = $pg->table('customers')
                        ->where('code', $customer['code'])
                        ->first();

                    $data = [
                        'company_id' => $companyId,
                        'name' => $customer['name'],
                        'email' => $customer['email'] ?? null,
                        'phone' => $customer['phone'] ?? null,
                        'address' => $customer['address'] ?? null,
                        'status' => $customer['status'] ?? 'active',
                        'document_type' => $customer['document_type'] ?? null,
                        'document_number' => $customer['document_number'] ?? null,
                        'updated_at' => now(),
                    ];

                    if ($exists) {
                        $pg->table('customers')
                            ->where('code', $customer['code'])
                            ->update($data);
                        $updatedCount++;
                    } else {
                        $data['code'] = $customer['code'];
                        $data['created_at'] = now();
                        $pg->table('customers')->insert($data);
                        $insertedCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'code' => $customer['code'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pg->commit();

            Log::info('Customers synced from Python', [
                'company_id' => $companyId,
                'total' => count($customers),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customers synchronized successfully',
                'data' => [
                    'total_processed' => count($customers),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            if (isset($pg) && $pg->getConnectionLevel() !== null) {
                $pg->rollBack();
            }

            Log::error('Error syncing customers from Python', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error synchronizing customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recibir y sincronizar quotes desde MySQL (Python lee de MySQL y envía a PostgreSQL).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncQuotes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'quotes' => 'required|array|max:1000',
            'quotes.*.mysql_quote_id' => 'required|integer',
            'quotes.*.quote_number' => 'required|string|max:50',
            'quotes.*.customer_id' => 'required|integer',
            'quotes.*.seller_id' => 'nullable|integer',
            'quotes.*.subtotal' => 'required|numeric',
            'quotes.*.tax' => 'nullable|numeric',
            'quotes.*.total' => 'required|numeric',
            'quotes.*.status' => 'nullable|string|max:20',
            'quotes.*.items' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $quotes = $request->input('quotes');

        try {
            $pg = DB::connection('pgsql');
            $pg->select('SELECT 1');

            $insertedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            $pg->beginTransaction();

            foreach ($quotes as $quote) {
                try {
                    $exists = $pg->table('quotes')
                        ->where('mysql_quote_id', $quote['mysql_quote_id'])
                        ->first();

                    $quoteData = [
                        'quote_number' => $quote['quote_number'],
                        'customer_id' => $quote['customer_id'],
                        'seller_id' => $quote['seller_id'] ?? null,
                        'company_id' => $companyId,
                        'subtotal' => $quote['subtotal'],
                        'tax' => $quote['tax'] ?? 0,
                        'total' => $quote['total'],
                        'status' => $quote['status'] ?? 'draft',
                        'mysql_quote_id' => $quote['mysql_quote_id'],
                        'synced_from_mysql' => true,
                        'updated_at' => now(),
                    ];

                    if ($exists) {
                        $pg->table('quotes')
                            ->where('mysql_quote_id', $quote['mysql_quote_id'])
                            ->update($quoteData);
                        $updatedCount++;
                    } else {
                        $quoteData['created_at'] = now();
                        $pgQuoteId = $pg->table('quotes')->insertGetId($quoteData, 'id');
                        $insertedCount++;

                        // Sincronizar items si existen
                        if (isset($quote['items']) && is_array($quote['items'])) {
                            $this->syncQuoteItems($pgQuoteId, $quote['items'], $pg);
                        }
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'mysql_quote_id' => $quote['mysql_quote_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pg->commit();

            Log::info('Quotes synced from Python', [
                'company_id' => $companyId,
                'total' => count($quotes),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quotes synchronized successfully',
                'data' => [
                    'total_processed' => count($quotes),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            if (isset($pg) && $pg->getConnectionLevel() !== null) {
                $pg->rollBack();
            }

            Log::error('Error syncing quotes from Python', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error synchronizing quotes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar items de una quote.
     */
    protected function syncQuoteItems($pgQuoteId, $items, $pg)
    {
        foreach ($items as $item) {
            try {
                $exists = $pg->table('quote_items')
                    ->where('mysql_item_id', $item['mysql_item_id'])
                    ->first();

                $itemData = [
                    'quote_id' => $pgQuoteId,
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total'],
                    'mysql_item_id' => $item['mysql_item_id'],
                    'updated_at' => now(),
                ];

                if ($exists) {
                    $pg->table('quote_items')
                        ->where('mysql_item_id', $item['mysql_item_id'])
                        ->update($itemData);
                } else {
                    $itemData['created_at'] = now();
                    $pg->table('quote_items')->insert($itemData);
                }
            } catch (\Exception $e) {
                Log::warning('Error syncing quote item', [
                    'mysql_item_id' => $item['mysql_item_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Recibir y sincronizar sellers desde Python.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncSellers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'sellers' => 'required|array|max:1000',
            'sellers.*.code' => 'required|string|max:50',
            'sellers.*.name' => 'required|string|max:255',
            'sellers.*.email' => 'nullable|email|max:255',
            'sellers.*.phone' => 'nullable|string|max:50',
            'sellers.*.percent_sales' => 'nullable|numeric',
            'sellers.*.seller_status' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $sellers = $request->input('sellers');

        try {
            $pg = DB::connection('pgsql');
            $pg->select('SELECT 1');

            $insertedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            $pg->beginTransaction();

            foreach ($sellers as $seller) {
                try {
                    $exists = $pg->table('sellers')
                        ->where('code', $seller['code'])
                        ->where('company_id', $companyId)
                        ->first();

                    $data = [
                        'name' => $seller['name'],
                        'email' => $seller['email'] ?? null,
                        'phone' => $seller['phone'] ?? null,
                        'percent_sales' => $seller['percent_sales'] ?? 0,
                        'seller_status' => $seller['seller_status'] ?? 'active',
                        'company_id' => $companyId,
                        'updated_at' => now(),
                    ];

                    if ($exists) {
                        $pg->table('sellers')
                            ->where('code', $seller['code'])
                            ->where('company_id', $companyId)
                            ->update($data);
                        $updatedCount++;
                    } else {
                        $data['code'] = $seller['code'];
                        $data['created_at'] = now();
                        $pg->table('sellers')->insert($data);
                        $insertedCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'code' => $seller['code'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pg->commit();

            Log::info('Sellers synced from Python', [
                'company_id' => $companyId,
                'total' => count($sellers),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sellers synchronized successfully',
                'data' => [
                    'total_processed' => count($sellers),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            if (isset($pg) && $pg->getConnectionLevel() !== null) {
                $pg->rollBack();
            }

            Log::error('Error syncing sellers from Python', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error synchronizing sellers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recibir y sincronizar categories desde Python.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'categories' => 'required|array|max:1000',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.description' => 'nullable|string|max:500',
            'categories.*.status' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $categories = $request->input('categories');

        try {
            $pg = DB::connection('pgsql');
            $pg->select('SELECT 1');

            $insertedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            $pg->beginTransaction();

            foreach ($categories as $category) {
                try {
                    $exists = $pg->table('categories')
                        ->where('name', $category['name'])
                        ->where('company_id', $companyId)
                        ->first();

                    $data = [
                        'name' => $category['name'],
                        'description' => $category['description'] ?? null,
                        'status' => $category['status'] ?? 'active',
                        'company_id' => $companyId,
                        'updated_at' => now(),
                    ];

                    if ($exists) {
                        $pg->table('categories')
                            ->where('name', $category['name'])
                            ->where('company_id', $companyId)
                            ->update($data);
                        $updatedCount++;
                    } else {
                        $data['created_at'] = now();
                        $pg->table('categories')->insert($data);
                        $insertedCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'name' => $category['name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $pg->commit();

            Log::info('Categories synced from Python', [
                'company_id' => $companyId,
                'total' => count($categories),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categories synchronized successfully',
                'data' => [
                    'total_processed' => count($categories),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            if (isset($pg) && $pg->getConnectionLevel() !== null) {
                $pg->rollBack();
            }

            Log::error('Error syncing categories from Python', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error synchronizing categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 OBTENER PRODUCTOS desde PostgreSQL para Python (PULL).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'per_page' => 'nullable|integer|max:10000|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $perPage = $request->input('per_page', 10000);

        try {
            $pg = DB::connection('pgsql');

            // Obtener productos de PostgreSQL remoto
            $query = $pg->table('products')
                ->where('company_id', $companyId)
                ->orderBy('code');

            // Paginar si se solicita
            if ($perPage) {
                $products = $query->limit($perPage)->get();
            } else {
                $products = $query->get();
            }

            return response()->json([
                'success' => true,
                'data' => $products,
                'pagination' => [
                    'total' => count($products),
                    'per_page' => $perPage
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting products from PostgreSQL', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 OBTENER CUSTOMERS desde PostgreSQL para Python (PULL).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
            'per_page' => 'nullable|integer|max:10000|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');
        $perPage = $request->input('per_page', 10000);

        try {
            $pg = DB::connection('pgsql');

            // Obtener customers de PostgreSQL remoto
            $query = $pg->table('customers')
                ->where('company_id', $companyId)
                ->orderBy('id');

            // Paginar si se solicita
            if ($perPage) {
                $customers = $query->limit($perPage)->get();
            } else {
                $customers = $query->get();
            }

            return response()->json([
                'success' => true,
                'data' => $customers,
                'pagination' => [
                    'total' => count($customers),
                    'per_page' => $perPage
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customers from PostgreSQL', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 OBTENER SELLERS desde PostgreSQL para Python (PULL).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSellers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = $request->input('company_id');

        try {
            $pg = DB::connection('pgsql');

            // Obtener sellers de PostgreSQL remoto
            $sellers = $pg->table('sellers')
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sellers,
                'pagination' => [
                    'total' => count($sellers)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting sellers from PostgreSQL', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting sellers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
