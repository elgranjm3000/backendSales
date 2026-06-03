<?php
  namespace App\Http\Controllers\Api;

  use App\Http\Controllers\Controller;
  use App\Models\Product;        // Modelo en BD de la API
  use App\Models\Customer;       // Modelo en BD de la API
  use App\Models\Category;       // Modelo en BD de la API
  use App\Models\Seller;         // Modelo en BD de la API
  use App\Models\Quote;          // Modelo en BD de la API
  use App\Models\User;           // Usuario de la API
  use App\Models\Company;        // Modelo en BD de la API
  use App\Models\Acceso;        // Modelo en BD de la API
  use App\Models\Subscription;   // Modelo en BD de la API
  use App\Models\BatchSyncLog;   // Modelo para logs de sincronización
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Str;

  class SyncController extends Controller
  {

      // =========================================================================
      // COMPANY
      // =========================================================================

      public function validateCompany(Request $request)
      {
          $request->validate([
              'rif' => 'required|string|max:50',
              'email' => 'required|email|max:255',
          ]);
          
          $emailLower = Str::lower($request->email);

          $acceso = Acceso::where('codigo', $request->rif)
                    ->where('correo_electronico', $emailLower)
                    ->first();

            if (!$acceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'El RIF o correo electrónico no están autorizados en el sistema de acceso.'
                ], 403); // 403 Forbidden o 404 Not Found según prefieras
            }

          $company = Company::where('rif', $request->rif)
                            ->where('email', $emailLower)
                            ->first();

          if ($company) {
              // Verificar si el usuario ya tiene suscripción para esta compañía
             

              return response()->json([
                  'success' => true,
                  'company_id' => $company->id,
                  'company' => [
                      'id' => $company->id,
                      'name' => $company->name,
                      'rif' => $company->rif,
                      'email' => $company->email,
                  ],                  
                  'message' => 'Company validada'
              ], 200);
          }

          // Crear nueva empresa con key_system_items_id por defecto
          $company = Company::create([
              //'user_id' => $request->user()->id, // Asignar al usuario autenticado
              'rif' => $request->rif,
              'email' => strtolower($request->email),
              'name' => $acceso->nombre,
              'address'=>$acceso->direccion,
              'phone'=>$acceso->telefono,
              'status' => 'active',
              'key_system_items_id' => 1, // Valor por defecto
          ]);
          

          return response()->json([
              'success' => true,
              'company_id' => $company->id,
              'company' => [
                  'id' => $company->id,
                  'name' => $company->name,
                  'rif' => $company->rif,
                  'email' => $company->email,
              ],              
              'message' => 'Compañia creada con exito'
          ], 201);
      }

      // =========================================================================
      // PRODUCTS
      // =========================================================================

      /**
       * Obtener productos de una empresa
       * GET /api/sync-batch/products?company_id=1&search=laptop
       */
      public function getProducts(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'search' => 'nullable|string|max:255',
              'category_id' => 'nullable|integer',
              'from_date' => 'nullable|date',
          ]);

          $query = Product::where('company_id', $request->company_id);

          if ($request->has('search')) {
              $search = $request->search;
              $query->where(function ($q) use ($search) {
                  $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
              });
          }

          if ($request->has('category_id')) {
              $query->where('category_id', $request->category_id);
          }

          if ($request->has('from_date')) {
              $query->where('created_at', '>=', $request->from_date);
          }

          $products = $query->orderBy('created_at', 'desc')
              ->paginate(50);

          return response()->json([
              'success' => true,
              'data' => $products
          ], 200);
      }

      public function syncProductsBatch(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'products' => 'required|array',
              'products.*.code' => 'required|string|max:50',
              'products.*.name' => 'required|string|max:255',
          ]);

          $startedAt = now();
          $companyId = $request->company_id;
          $products = $request->products;
          $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
          $errors = [];

          // Validar límite de registros
          if (count($products) > 5000) {
              return response()->json([
                  'success' => false,
                  'message' => 'El número máximo de registros por lote es 5000',
                  'provided' => count($products),
                  'max_allowed' => 5000
              ], 422);
          }

          DB::transaction(function () use ($products, $companyId, &$stats, &$errors) {
              foreach ($products as $index => $productData) {
                  $imageData = $productData['product_image'] ?? null;
                  $imageType = $productData['image_type'] ?? null;
                  unset($productData['product_image'], $productData['image_type']);

                  try {
                      // Si viene category_code, buscar category_id
                      if (isset($productData['category_id'])) {
                          $category = Category::where('name', $productData['category_id'])
                              ->where('company_id', $companyId)
                              ->first();

                          if ($category) {
                              $productData['category_id'] = $category->id;
                          } else {
                              // Opción 1: Dejar category_id como null
                              $productData['category_id'] = null;
                          }

                          // Eliminar category_code antes de guardar
                          unset($productData['category_code']);
                      }

                      // Buscar producto por code + company_id
                      $product = Product::where('code', $productData['code'])
                          ->where('company_id', $companyId)
                          ->first();

                      if ($product) {
                          $product->update($productData);
                          $stats['updated']++;
                      } else {
                          $productData['company_id'] = $companyId;
                          $product = Product::create($productData);
                          $stats['created']++;
                      }
                      
                        
                         if ($imageData && $imageType && $product) {
                              try {
                                  // No decodificar en PHP, dejar que PostgreSQL lo haga
                                  DB::statement("
                                      UPDATE products
                                      SET image_type = ?,
                                          product_image = decode(?, 'base64')
                                      WHERE id = ?
                                  ", [$imageType, $imageData, $product->id]);
                              } catch (\Exception $imgError) {
                                  \Log::error("Error imagen: " . $imgError->getMessage());
                              }
                          }

                        DB::commit();

      
                      
                  } catch (\Exception $e) {
                      $stats['errors']++;
                      $errors[] = [
                          'index' => $index,
                          'code' => $productData['code'] ?? 'unknown',
                          'error' => $e->getMessage()
                      ];
                  }
              }
          });

          // Determinar el estado del sync
          $status = BatchSyncLog::STATUS_COMPLETED;
          if ($stats['errors'] > 0) {
              $status = ($stats['created'] + $stats['updated']) > 0
                  ? BatchSyncLog::STATUS_PARTIAL
                  : BatchSyncLog::STATUS_FAILED;
          }

          // Guardar log de sincronización
          BatchSyncLog::create([
              'company_id' => $companyId,
              'user_id' => auth()->id(),
              'entity_type' => BatchSyncLog::ENTITY_PRODUCTS,
              'records_processed' => count($products),
              'records_created' => $stats['created'],
              'records_updated' => $stats['updated'],
              'records_failed' => $stats['errors'],
              'status' => $status,
              'error_details' => $errors,
              'started_at' => $startedAt,
              'completed_at' => now(),
          ]);

          return response()->json([
              'success' => $stats['errors'] === 0 || ($stats['created'] + $stats['updated']) > 0,
              'created' => $stats['created'],
              'updated' => $stats['updated'],
              'errors' => $stats['errors'],
              'error_details' => $errors,
              'synced_at' => now()->toIso8601String()
          ], 200);
      }


      public function destroyProductsBatch(Request $request)
      {
          return $this->_destroyBatch($request, new Product(), 'code', 'codes');
      }

      // =========================================================================
      // CUSTOMERS
      // =========================================================================

      /**
       * Obtener clientes de una empresa
       * GET /api/sync-batch/customers?company_id=1&search=juan
       */
      public function getCustomers(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'search' => 'nullable|string|max:255',
              'from_date' => 'nullable|date',
          ]);

          $query = Customer::where('company_id', $request->company_id);

          if ($request->has('search')) {
              $search = $request->search;
              $query->where(function ($q) use ($search) {
                  $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('document_number', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
              });
          }

          if ($request->has('from_date')) {
              $query->where('created_at', '>=', $request->from_date);
          }

          $customers = $query->orderBy('created_at', 'desc')
              ->paginate(50);

          return response()->json([
              'success' => true,
              'data' => $customers
          ], 200);
      }

      public function syncCustomersBatch(Request $request)
      {
          return $this->_syncBatch(
              request: $request,
              entityName: 'customers',
              model: new Customer(),
              keyField: 'codigo',
              validationRules: [
                  'company_id' => 'required|integer',
                  'customers' => 'required|array',
                  'customers.*.name' => 'required|string|max:255',
                  'customers.*.document_number' => 'required|string|max:50',
                  'customers.*.codigo' => 'nullable|string|max:50',
              ],
              entityType: BatchSyncLog::ENTITY_CUSTOMERS
          );
      }

      public function destroyCustomersBatch(Request $request)
      {
          return $this->_destroyBatch($request, new Customer(), 'codigo', 'documents');
      }

      // =========================================================================
      // CATEGORIES
      // =========================================================================

      /**
       * Obtener categorías de una empresa
       * GET /api/sync-batch/categories?company_id=1&search=electro
       */
      public function getCategories(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'search' => 'nullable|string|max:255',
              'from_date' => 'nullable|date',
          ]);

          $query = Category::where('company_id', $request->company_id);

          if ($request->has('search')) {
              $search = $request->search;
              $query->where('name', 'ilike', "%{$search}%");
          }

          if ($request->has('from_date')) {
              $query->where('created_at', '>=', $request->from_date);
          }

          $categories = $query->orderBy('name', 'asc')
              ->paginate(50);

          return response()->json([
              'success' => true,
              'data' => $categories
          ], 200);
      }

      public function syncCategoriesBatch(Request $request)
      {
          return $this->_syncBatch(
              request: $request,
              entityName: 'categories',
              model: new Category(),
              keyField: 'name',
              validationRules: [
                  'company_id' => 'required|integer',
                  'categories' => 'required|array',
                  'categories.*.name' => 'required|string|max:255',
              ],
              entityType: BatchSyncLog::ENTITY_CATEGORIES
          );
      }

      public function destroyCategoriesBatch(Request $request)
      {
          return $this->_destroyBatch($request, new Category(), 'name', 'names');
      }

      // =========================================================================
      // SELLERS
      // =========================================================================

      /**
       * Obtener vendedores de una empresa
       * GET /api/sync-batch/sellers?company_id=1&search=juan
       */
      public function getSellers(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'search' => 'nullable|string|max:255',
              'from_date' => 'nullable|date',
          ]);

          $query = Seller::with('user:id,name,email')
              ->where('company_id', $request->company_id);

          if ($request->has('search')) {
              $search = $request->search;
              $query->where(function ($q) use ($search) {
                  $q->where('code', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'ilike', "%{$search}%")
                                   ->orWhere('email', 'ilike', "%{$search}%");
                    });
              });
          }

          if ($request->has('from_date')) {
              $query->where('created_at', '>=', $request->from_date);
          }

          $sellers = $query->orderBy('created_at', 'desc')
              ->paginate(50);

          return response()->json([
              'success' => true,
              'data' => $sellers
          ], 200);
      }

      public function syncSellersBatch(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'sellers' => 'required|array',
              'sellers.*.code' => 'required|string|max:50',
              'sellers.*.description' => 'required|string|max:255',
              'sellers.*.email' => 'required|email|max:255',
          ]);

          $sellers = $request->sellers;
          $companyId = $request->company_id;

          // Validar emails duplicados dentro del mismo batch
          $emails = array_column($sellers, 'email');
          $duplicateEmails = array_filter(array_count_values($emails), fn($count) => $count > 1);

          if (!empty($duplicateEmails)) {
              return response()->json([
                  'success' => false,
                  'message' => 'Emails duplicados en el lote',
                  'duplicate_emails' => array_keys($duplicateEmails),
                  'error' => 'Los siguientes emails se repiten dentro del lote: ' . implode(', ', array_keys($duplicateEmails))
              ], 422);
          }

          // Validar emails que ya existen en otra compañía
          $existingUsers = User::whereIn('email', $emails)
              ->with('sellers')
              ->get();

          $existingEmailsInfo = [];
          $existingEmails = [];

          foreach ($existingUsers as $user) {
              // Obtener la compañía del usuario según su rol
              $userCompanyId = null;
              $roleDescription = '';

              switch ($user->role->value) {
                  case 'company':
                      $userCompanyId = $user->companies()->first()?->id;
                      $roleDescription = 'compañía';
                      break;
                  case 'seller':
                      $userCompanyId = $user->sellers()->first()?->company_id;
                      $roleDescription = 'vendedor';
                      break;
                  case 'cajero':
                      $roleDescription = 'sincronizador';
                      // Cajero no tiene compañía asociada directamente
                      break;
                  case 'admin':
                      $roleDescription = 'administrador';
                      break;
                  case 'manager':
                      $roleDescription = 'super administrador';
                      break;
                  default:
                      $roleDescription = $user->role->value;
              }

              // Solo es error si está en otra compañía
              if ($userCompanyId && $userCompanyId != $companyId) {
                  $existingEmails[] = $user->email;
                  $existingEmailsInfo[] = [
                      'email' => $user->email,
                      'role' => $user->role->value,
                      'role_description' => $roleDescription,
                      'company_id' => $userCompanyId,
                  ];
              } elseif ($userCompanyId === null && in_array($user->role->value, ['admin', 'manager', 'cajero'])) {
                  // Admin, Manager y Cajero no pueden tener sellers
                  $existingEmails[] = $user->email;
                  $existingEmailsInfo[] = [
                      'email' => $user->email,
                      'role' => $user->role->value,
                      'role_description' => $roleDescription,
                      'company_id' => null,
                  ];
              }
          }

          if (!empty($existingEmails)) {
              // Construir mensaje detallado
              $errorMessages = [];
              foreach ($existingEmailsInfo as $info) {
                  $msg = "{$info['email']} (rol: {$info['role_description']}";
                  if ($info['company_id']) {
                      $msg .= ", compañía ID: {$info['company_id']}";
                  }
                  $msg .= ")";
                  $errorMessages[] = $msg;
              }

              return response()->json([
                  'success' => false,
                  'message' => 'Emails ya existen en el sistema',
                  'existing_emails' => $existingEmails,
                  'existing_emails_info' => $existingEmailsInfo,
                  'error' => 'Los siguientes emails ya están en uso: ' . implode(', ', $errorMessages)
              ], 422);
          }

          $startedAt = now();
          $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
          $errors = [];

          // Validar límite de registros
          if (count($sellers) > 5000) {
              return response()->json([
                  'success' => false,
                  'message' => 'El número máximo de registros por lote es 5000',
                  'provided' => count($sellers),
                  'max_allowed' => 5000
              ], 422);
          }

          DB::transaction(function () use ($sellers, $companyId, &$stats, &$errors) {
              foreach ($sellers as $index => $sellerData) {
                  try {
                      // Buscar o crear usuario PRIMERO
                      $user = User::where('email', $sellerData['email'])->first();

                      if (!$user) {
                          $user = User::create([
                              'name' => $sellerData['description'],
                              'email' => $sellerData['email'],
                              'password' => $sellerData['password'], // Ya viene hasheado
                              'role' => 'seller',
                              'status' => 'active',
                          ]);
                      }else{
                          $user->update([
                              'name' => $sellerData['description'],
                              'password' => $sellerData['password'],
                          ]);

                      }

                      // Buscar seller por (user_id, company_id) - clave única real
                      $seller = Seller::where('user_id', $user->id)
                          ->where('company_id', $companyId)
                          ->first();

                      $sellerData['user_id'] = $user->id;
                      unset($sellerData['email'], $sellerData['password']);

                      if ($seller) {
                          $seller->update($sellerData);
                          $stats['updated']++;
                      } else {
                          $sellerData['company_id'] = $companyId;
                          Seller::create($sellerData);
                          $stats['created']++;
                      }
                  } catch (\Exception $e) {
                      $stats['errors']++;
                      $errors[] = [
                          'index' => $index,
                          'code' => $sellerData['code'] ?? 'unknown',
                          'error' => $e->getMessage()
                      ];
                  }
              }
          });

          // Determinar el estado del sync
          $status = BatchSyncLog::STATUS_COMPLETED;
          if ($stats['errors'] > 0) {
              $status = ($stats['created'] + $stats['updated']) > 0
                  ? BatchSyncLog::STATUS_PARTIAL
                  : BatchSyncLog::STATUS_FAILED;
          }

          // Guardar log de sincronización
          BatchSyncLog::create([
              'company_id' => $companyId,
              'user_id' => auth()->id(),
              'entity_type' => BatchSyncLog::ENTITY_SELLERS,
              'records_processed' => count($sellers),
              'records_created' => $stats['created'],
              'records_updated' => $stats['updated'],
              'records_failed' => $stats['errors'],
              'status' => $status,
              'error_details' => $errors,
              'started_at' => $startedAt,
              'completed_at' => now(),
          ]);

          return response()->json([
              'success' => $stats['errors'] === 0 || ($stats['created'] + $stats['updated']) > 0,
              'created' => $stats['created'],
              'updated' => $stats['updated'],
              'errors' => $stats['errors'],
              'error_details' => $errors,
              'synced_at' => now()->toIso8601String()
          ], 200);
      }


      public function destroySellersBatch(Request $request)
      {
          return $this->_destroyBatch($request, new Seller(), 'code', 'codes');
          
                  /*$request->validate([
                      'company_id' => 'required|integer',
                      'codes' => 'required|array'
                  ]);
            
                  // Obtener los user_id de los vendedores a eliminar
                  $userIds = Seller::where('company_id', $request->company_id)
                      ->whereIn('code', $request->codes)
                      ->pluck('user_id')
                      ->filter()
                      ->unique()
                      ->toArray();
            
                  if (empty($userIds)) {
                      return response()->json([
                          'success' => true,
                          'deleted' => 0
                      ]);
                  }
            
                  // Borrar usuarios → cascada borra los sellers automáticamente
                  $deleted = User::whereIn('id', $userIds)->delete();
            
                  return response()->json([
                      'success' => true,
                      'deleted' => $deleted
                  ]);*/
          
          
      }

      // =========================================================================
      // SYNC HISTORY
      // =========================================================================

      /**
       * Obtener historial de sincronizaciones
       * GET /api/sync-batch/history?company_id=1&entity_type=products&from_date=2026-01-01
       */
      public function getSyncHistory(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'entity_type' => 'nullable|string|in:products,customers,categories,sellers,quotes',
              'from_date' => 'nullable|date',
              'status' => 'nullable|string|in:completed,partial,failed',
          ]);

          $query = BatchSyncLog::with(['user:id,name,email', 'company:id,name'])
              ->where('company_id', $request->company_id);

          if ($request->has('entity_type')) {
              $query->where('entity_type', $request->entity_type);
          }

          if ($request->has('from_date')) {
              $query->where('created_at', '>=', $request->from_date);
          }

          if ($request->has('status')) {
              $query->where('status', $request->status);
          }

          $logs = $query->orderBy('created_at', 'desc')
              ->paginate(50);

          return response()->json([
              'success' => true,
              'data' => $logs
          ], 200);
      }

      /**
       * Obtener última sincronización de cada entidad
       * GET /api/sync-batch/last-sync?company_id=1
       */
      public function getLastSync(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
          ]);

          $company = Company::find($request->company_id);

          if (!$company) {
              return response()->json([
                  'success' => false,
                  'message' => 'Company not found'
              ], 404);
          }

          $lastSyncs = [];

          $entityTypes = [
              BatchSyncLog::ENTITY_PRODUCTS,
              BatchSyncLog::ENTITY_CUSTOMERS,
              BatchSyncLog::ENTITY_CATEGORIES,
              BatchSyncLog::ENTITY_SELLERS,
          ];

          foreach ($entityTypes as $entityType) {
              $lastSync = BatchSyncLog::byCompany($request->company_id)
                  ->byEntity($entityType)
                  ->successful()
                  ->completed()
                  ->orderBy('completed_at', 'desc')
                  ->first();

              $lastSyncs[$entityType] = $lastSync ? [
                  'id' => $lastSync->id,
                  'entity_type' => $lastSync->entity_type,
                  'entity_type_name' => $lastSync->entity_type_name,
                  'records_processed' => $lastSync->records_processed,
                  'records_created' => $lastSync->records_created,
                  'records_updated' => $lastSync->records_updated,
                  'records_failed' => $lastSync->records_failed,
                  'status' => $lastSync->status,
                  'status_name' => $lastSync->status_name,
                  'completed_at' => $lastSync->completed_at,
                  'duration_seconds' => $lastSync->duration_seconds,
                  'duration_formatted' => $lastSync->duration_formatted,
              ] : null;
          }

          return response()->json([
              'success' => true,
              'company_id' => $request->company_id,
              'last_syncs' => $lastSyncs
          ], 200);
      }

      // =========================================================================
      // QUOTES (Presupuestos creados en la API, NO desde PostgreSQL)
      // =========================================================================

      /**
       * Crear un nuevo quote
       * POST /api/v1/sync/quotes
       *
       * Los quotes se crean desde web/app de la API, no vienen de PostgreSQL
       */
      public function createQuote(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'quote_number' => 'required|string|max:50',
              'customer_id' => 'required|integer',
              'user_seller_id' => 'nullable|integer',
              'subtotal' => 'required|numeric',
              'tax_amount' => 'required|numeric',
              'discount' => 'nullable|numeric',
              'discount_amount' => 'nullable|numeric',
              'total' => 'required|numeric',
              'bcv_rate' => 'nullable|numeric',
              'status' => 'required|string|max:50',
              'items' => 'required|array',
              'items.*.product_id' => 'required|integer',
              'items.*.quantity' => 'required|numeric|min:1',
              'items.*.price' => 'required|numeric',
          ]);

          DB::transaction(function () use ($request, &$quote) {
              // Crear el quote
              $quote = Quote::create([
                  'company_id' => $request->company_id,
                  'quote_number' => $request->quote_number,
                  'customer_id' => $request->customer_id,
                  'user_seller_id' => $request->user_seller_id,
                  'subtotal' => $request->subtotal,
                  'tax_amount' => $request->tax_amount,
                  'discount' => $request->discount ?? 0,
                  'discount_amount' => $request->discount_amount ?? 0,
                  'total' => $request->total,
                  'bcv_rate' => $request->bcv_rate ?? 1,
                  'status' => $request->status,
                  'created_at' => now(),
                  'updated_at' => now(),
              ]);

              // Crear items del quote
              foreach ($request->items as $item) {
                  $quote->items()->create([
                      'product_id' => $item['product_id'],
                      'name' => $item['name'] ?? null,
                      'item_type' => $item['item_type'] ?? 'product',
                      'unit' => $item['unit'] ?? 'pcs',
                      'quantity' => $item['quantity'],
                      'unit_price' => $item['price'], // Mapear 'price' a 'unit_price'
                      'discount_percentage' => $item['discount_percentage'] ?? 0,
                      'discount_amount' => $item['discount_amount'] ?? 0,
                      'tax_percentage' => $item['tax_percentage'] ?? 0,
                      'tax_amount' => $item['tax_amount'] ?? 0,
                      'buy_tax' => $item['buy_tax'] ?? 0,
                      'subtotal' => $item['subtotal'] ?? ($item['quantity'] * $item['price']),
                      'total' => $item['total'] ?? ($item['quantity'] * $item['price']),
                      'type_price' => $item['type_price'] ?? 'standard',
                      'sort_order' => $item['sort_order'] ?? 0,
                  ]);
              }
          });

          return response()->json([
              'success' => true,
              'quote_id' => $quote->id,
              'quote_number' => $quote->quote_number,
              'message' => 'Quote created successfully'
          ], 201);
      }

      /**
       * Actualizar estado de un quote
       * PUT /api/v1/sync/quotes/{id}/status
       */
      public function updateQuoteStatus(Request $request, $id)
      {
          $request->validate([
              'status' => 'required|string|in:pending,approved,rejected,canceled,completed',
          ]);

          $quote = null;
      
          // Si es numérico, buscar por id (columna integer)
          if (is_numeric($id)) {
              $quote = Quote::where('id', $id)
                  ->where('company_id', $request->company_id)
                  ->first();
          }
    
          // Si no se encontró, buscar por quote_number (columna string)
          if (!$quote) {
              $quote = Quote::where('quote_number', $id)
                  ->where('company_id', $request->company_id)
                  ->first();
          }



          if (!$quote) {
              return response()->json([
                  'success' => false,
                  'error' => 'Quote not found'
              ], 404);
          }

          $quote->update([
              'status' => $request->status,
              'updated_at' => now(),
          ]);

          return response()->json([
              'success' => true,
              'quote_id' => $quote->id,
              'status' => $quote->status
          ], 200);
      }

      /**
       * Obtener quotes de una empresa
       * GET /api/v1/sync/quotes?company_id=27&status=pending
       */
      public function getQuotes(Request $request)
      {
          $request->validate([
              'company_id' => 'required|integer',
              'status' => 'nullable|string',
              'from_date' => 'nullable|date',
          ]);

          $query = Quote::with(['items.product', 'customer', 'seller','sellerData'])
              ->where('company_id', $request->company_id);

          if ($request->has('status')) {
              $query->where('status', $request->status);
          }

          if ($request->has('from_date')) {
              $query->where('created_at', '>=', $request->from_date);
          }

          $quotes = $query->orderBy('created_at', 'desc')->get();
          
          $quotes = $quotes->map(function ($quote) {
      if ($quote->sellerData && $quote->sellerData->company_id == $quote->company_id) {
          $quote->seller->code = $quote->sellerData->code;
      }
      unset($quote->sellerData, $quote->sellerData);
      return $quote;
  });


          return response()->json([
              'success' => true,
              'quotes' => $quotes
          ], 200);
      }

      /**
       * Eliminar un quote
       * DELETE /api/v1/sync/quotes/{id}
       */
      public function destroyQuote(Request $request, $id)
      {
          $quote = Quote::where('id', $id)
              ->where('company_id', $request->company_id)
              ->first();

          if (!$quote) {
              return response()->json([
                  'success' => false,
                  'error' => 'Quote not found'
              ], 404);
          }

          $quote->items()->delete(); // Eliminar items primero
          $quote->delete(); // Eliminar quote

          return response()->json([
              'success' => true,
              'message' => 'Quote deleted successfully'
          ], 200);
      }

      // =========================================================================
      // MÉTODOS PRIVADOS
      // =========================================================================

      private function _syncBatch(Request $request, string $entityName, $model,
                                  string $keyField, array $validationRules, string $entityType)
      {
          $request->validate($validationRules);

          $startedAt = now();
          $companyId = $request->company_id;
          $entities = $request->input($entityName);
          $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
          $errors = [];

          // Validar límite de registros
          if (count($entities) > 5000) {
              return response()->json([
                  'success' => false,
                  'message' => 'El número máximo de registros por lote es 5000',
                  'provided' => count($entities),
                  'max_allowed' => 5000
              ], 422);
          }

          DB::transaction(function () use ($model, $keyField, $companyId, $entities, &$stats, &$errors) {
              foreach ($entities as $index => $entityData) {
                  try {
                      $entity = $model->where($keyField, $entityData[$keyField])
                          ->where('company_id', $companyId)
                          ->first();

                      if ($entity) {
                          $entity->update($entityData);
                          $stats['updated']++;
                      } else {
                          $entityData['company_id'] = $companyId;
                          $model->create($entityData);
                          $stats['created']++;
                      }
                  } catch (\Exception $e) {
                      $stats['errors']++;
                      $errors[] = [
                          'index' => $index,
                          'key' => $entityData[$keyField] ?? 'unknown',
                          'error' => $e->getMessage()
                      ];
                  }
              }
          });

          // Determinar el estado del sync
          $status = BatchSyncLog::STATUS_COMPLETED;
          if ($stats['errors'] > 0) {
              $status = ($stats['created'] + $stats['updated']) > 0
                  ? BatchSyncLog::STATUS_PARTIAL
                  : BatchSyncLog::STATUS_FAILED;
          }

          // Guardar log de sincronización
          BatchSyncLog::create([
              'company_id' => $companyId,
              'user_id' => auth()->id(),
              'entity_type' => $entityType,
              'records_processed' => count($entities),
              'records_created' => $stats['created'],
              'records_updated' => $stats['updated'],
              'records_failed' => $stats['errors'],
              'status' => $status,
              'error_details' => $errors,
              'started_at' => $startedAt,
              'completed_at' => now(),
          ]);

          return response()->json([
              'success' => $stats['errors'] === 0 || ($stats['created'] + $stats['updated']) > 0,
              'created' => $stats['created'],
              'updated' => $stats['updated'],
              'errors' => $stats['errors'],
              'error_details' => $errors,
              'synced_at' => now()->toIso8601String()
          ], 200);
      }

      private function _destroyBatch(Request $request, $model, string $keyField, string $keyParam)
      {
          $request->validate([
              'company_id' => 'required|integer',
              $keyParam => 'required|array'
          ]);

          $deleted = $model->where('company_id', $request->company_id)
              ->whereIn($keyField, $request->input($keyParam))
              ->delete();

          return response()->json([
              'success' => true,
              'deleted' => $deleted
          ]);
      }
  }
