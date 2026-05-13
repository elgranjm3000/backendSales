# 📘 SyncController API Documentation

## 🔐 Autenticación

Todos los endpoints requieren:
- **Header**: `Authorization: Bearer {token}`
- **Middleware**: `auth:sanctum` + `CheckSubscription`

### Obtener Token:

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password",
    "device_name": "my-app"
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": {...},
    "subscription": {
      "plan": "trial",
      "features": ["sync_products", "sync_customers", ...]
    }
  }
}
```

---

## 📋 Endpoints

Base URL: `/api/sync-batch`

### 1. COMPANY

#### 1.1 Validar o Crear Empresa

```http
POST /api/sync-batch/company/validate
```

**Suscripción requerida:** Activa (sin feature específico)

**Parámetros:**
```json
{
  "rif": "J123456789",        // Requerido | string | max:50
  "email": "empresa@test.com", // Requerido | email | max:255
  "name": "Mi Empresa SA"     // Opcional | string | max:255
}
```

**Ejemplo curl:**
```bash
curl -X POST http://localhost/api/sync-batch/company/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rif": "J123456789",
    "email": "empresa@test.com",
    "name": "Mi Empresa SA"
  }'
```

**Respuestas:**

- **201 Created** (Nueva empresa creada):
```json
{
  "success": true,
  "company_id": 20,
  "company": {
    "id": 20,
    "name": "Mi Empresa SA",
    "rif": "J123456789",
    "email": "empresa@test.com"
  }
}
```

- **200 OK** (Empresa existente encontrada):
```json
{
  "success": true,
  "company_id": 15,
  "company": {
    "id": 15,
    "name": "Empresa Existente",
    "rif": "J123456789",
    "email": "contacto@existente.com"
  }
}
```

---

### 2. PRODUCTS

#### 2.1 Sincronizar Productos (Crear/Actualizar)

```http
POST /api/sync-batch/products
```

**Suscripción requerida:** `sync_products`

**Parámetros:**
```json
{
  "company_id": 1,          // Requerido | integer
  "products": [             // Requerido | array | max: 5000 elementos
    {
      "code": "PROD001",           // Requerido | string | max:50 (clave única)
      "name": "Producto Ejemplo",   // Requerido | string | max:255
      "description": "Descripción", // Opcional | string
      "price": 100.00,             // Requerido | numeric
      "cost": 50.00,               // Requerido | numeric
      "higher_price": 120.00,      // Requerido | numeric
      "coin": "USD",               // Requerido | string | max:10
      "description_coin": "Dólares", // Requerido | string
      "stock": 100,                // Requerido | numeric
      "min_stock": 10,             // Requerido | numeric
      "category_id": 1,            // Requerido | integer
      "status": "active",          // Opcional | string | in:active,inactive
      "weight": 1.5,               // Requerido | numeric
      "unitary_cost": 50.0,        // Requerido | numeric
      "buy_tax": "0",              // Requerido | string
      "buy_aliquot": 0.0,          // Requerido | numeric
      "sale_tax": "16",            // Requerido | string
      "aliquot": 16.0              // Requerido | numeric
    }
  ]
}
```

**Ejemplo curl:**
```bash
curl -X POST http://localhost/api/sync-batch/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "products": [
      {
        "code": "PROD001",
        "name": "Laptop HP",
        "description": "Laptop HP 15.6\"",
        "price": 800.00,
        "cost": 600.00,
        "higher_price": 850.00,
        "coin": "USD",
        "description_coin": "Dólares",
        "stock": 50,
        "min_stock": 5,
        "category_id": 1,
        "status": "active",
        "weight": 2.5,
        "unitary_cost": 600.0,
        "buy_tax": "0",
        "buy_aliquot": 0.0,
        "sale_tax": "16",
        "aliquot": 16.0
      },
      {
        "code": "PROD002",
        "name": "Mouse Inalámbrico",
        "price": 25.00,
        "cost": 15.00,
        "higher_price": 30.00,
        "coin": "USD",
        "description_coin": "Dólares",
        "stock": 200,
        "min_stock": 20,
        "category_id": 1,
        "status": "active",
        "weight": 0.2,
        "unitary_cost": 15.0,
        "buy_tax": "0",
        "buy_aliquot": 0.0,
        "sale_tax": "16",
        "aliquot": 16.0
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "created": 5,
  "updated": 3,
  "errors": 0,
  "error_details": []
}
```

#### 2.2 Eliminar Productos

```http
DELETE /api/sync-batch/products
```

**Suscripción requerida:** `sync_products`

**Parámetros:**
```json
{
  "company_id": 1,      // Requerido | integer
  "codes": [            // Requerido | array
    "PROD001",
    "PROD002"
  ]
}
```

**Ejemplo curl:**
```bash
curl -X DELETE http://localhost/api/sync-batch/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "codes": ["PROD001", "PROD002", "PROD003"]
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "deleted": 3
}
```

---

### 3. CUSTOMERS

#### 3.1 Sincronizar Clientes

```http
POST /api/sync-batch/customers
```

**Suscripción requerida:** `sync_customers`

**Parámetros:**
```json
{
  "company_id": 1,     // Requerido | integer
  "customers": [       // Requerido | array | max: 5000
    {
      "document_number": "V12345678",  // Requerido | string | max:50 (clave única)
      "name": "Juan Pérez",           // Requerido | string | max:255
      "email": "juan@test.com",       // Opcional | email | max:255
      "phone": "+58-414-1234567",     // Opcional | string | max:20
      "address": "Calle 123",         // Opcional | string
      "status": "active"              // Opcional | string
    }
  ]
}
```

**Ejemplo curl:**
```bash
curl -X POST http://localhost/api/sync-batch/customers \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "customers": [
      {
        "document_number": "V12345678",
        "name": "Juan Pérez",
        "email": "juan@test.com",
        "phone": "+58-414-1234567",
        "address": "Av. Principal #123"
      },
      {
        "document_number": "V87654321",
        "name": "María González",
        "email": "maria@test.com",
        "phone": "+58-424-9876543"
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "created": 2,
  "updated": 0,
  "errors": 0
}
```

#### 3.2 Eliminar Clientes

```http
DELETE /api/sync-batch/customers
```

**Parámetros:**
```json
{
  "company_id": 1,       // Requerido | integer
  "documents": [          // Requerido | array
    "V12345678",
    "V87654321"
  ]
}
```

**Ejemplo curl:**
```bash
curl -X DELETE http://localhost/api/sync-batch/customers \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "documents": ["V12345678", "V87654321"]
  }'
```

---

### 4. CATEGORIES

#### 4.1 Sincronizar Categorías

```http
POST /api/sync-batch/categories
```

**Suscripción requerida:** `sync_categories`

**Parámetros:**
```json
{
  "company_id": 1,      // Requerido | integer
  "categories": [       // Requerido | array | max: 5000
    {
      "name": "Electrónica",          // Requerido | string | max:255 (clave única)
      "description": "Productos electrónicos", // Opcional | string
      "status": "active"              // Opcional | string
    }
  ]
}
```

**Ejemplo curl:**
```bash
curl -X POST http://localhost/api/sync-batch/categories \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "categories": [
      {
        "name": "Electrónica",
        "description": "Productos electrónicos"
      },
      {
        "name": "Ropa",
        "description": "Prendas de vestir"
      },
      {
        "name": "Alimentos",
        "description": "Productos alimenticios"
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "created": 3,
  "updated": 0,
  "errors": 0
}
```

#### 4.2 Eliminar Categorías

```http
DELETE /api/sync-batch/categories
```

**Parámetros:**
```json
{
  "company_id": 1,    // Requerido | integer
  "names": [           // Requerido | array
    "Electrónica",
    "Ropa"
  ]
}
```

---

### 5. SELLERS

#### 5.1 Sincronizar Vendedores

```http
POST /api/sync-batch/sellers
```

**Suscripción requerida:** `sync_sellers`

**Parámetros:**
```json
{
  "company_id": 1,     // Requerido | integer
  "sellers": [         // Requerido | array | max: 5000
    {
      "code": "SELLER01",               // Requerido | string | max:50 (clave única + company_id)
      "description": "Vendedor Juan",   // Requerido | string | max:255
      "email": "juan@company.com",      // Requerido | email | max:255
      "password": "$2y$10$hashed...",  // Requerido | string (bcrypt hash)
      "status": "active"                // Opcional | string
    }
  ]
}
```

**Nota:** `password` debe venir hasheado con bcrypt.

**Ejemplo PHP (hashear password):**
```php
$hashedPassword = bcrypt('password123');
```

**Ejemplo curl:**
```bash
curl -X POST http://localhost/api/sync-batch/sellers \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "sellers": [
      {
        "code": "SELLER01",
        "description": "Juan Pérez",
        "email": "juanp@company.com",
        "password": "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi",
        "status": "active"
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "created": 1,
  "updated": 0,
  "errors": 0
}
```

#### 5.2 Eliminar Vendedores

```http
DELETE /api/sync-batch/sellers
```

**Parámetros:**
```json
{
  "company_id": 1,    // Requerido | integer
  "codes": [           // Requerido | array
    "SELLER01",
    "SELLER02"
  ]
}
```

---

### 6. QUOTES

#### 6.1 Crear Quote

```http
POST /api/sync-batch/quotes
```

**Suscripción requerida:** `sync_quotes` (NO disponible en plan trial)

**Parámetros:**
```json
{
  "company_id": 1,              // Requerido | integer
  "quote_number": "QUOTE-001",  // Requerido | string | max:50
  "customer_id": 15,            // Requerido | integer
  "user_seller_id": 5,          // Opcional | integer
  "subtotal": 1000.00,          // Requerido | numeric
  "tax_amount": 160.00,         // Requerido | numeric
  "discount": 0,                // Opcional | numeric
  "discount_amount": 0,         // Opcional | numeric
  "total": 1160.00,             // Requerido | numeric
  "bcv_rate": 35.5,             // Opcional | numeric
  "status": "draft",            // Requerido | string | in:draft,sent,approved,rejected,expired
  "items": [                    // Requerido | array
    {
      "product_id": 10,              // Requerido | integer
      "name": "Producto Nombre",     // Opcional | string
      "item_type": "product",        // Opcional | string
      "unit": "pcs",                 // Opcional | string
      "quantity": 2,                 // Requerido | numeric | min:1
      "price": 150.00,               // Requerido | numeric (se mapea a unit_price)
      "discount_percentage": 0,      // Opcional | numeric
      "discount_amount": 0,          // Opcional | numeric
      "tax_percentage": 16,          // Opcional | numeric
      "tax_amount": 48,              // Opcional | numeric
      "buy_tax": 0,                  // Opcional | integer
      "subtotal": 300,               // Opcional | numeric
      "total": 348,                  // Opcional | numeric
      "type_price": "ST",            // Opcional | string | max:2
      "sort_order": 1                // Opcional | integer
    }
  ]
}
```

**Ejemplo curl:**
```bash
curl -X POST http://localhost/api/sync-batch/quotes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "quote_number": "QUOTE-2024-001",
    "customer_id": 15,
    "user_seller_id": 5,
    "subtotal": 1000.00,
    "tax_amount": 160.00,
    "discount": 0,
    "discount_amount": 0,
    "total": 1160.00,
    "bcv_rate": 35.5,
    "status": "draft",
    "items": [
      {
        "product_id": 10,
        "name": "Laptop HP",
        "item_type": "product",
        "unit": "pcs",
        "quantity": 2,
        "price": 500.00,
        "discount_percentage": 0,
        "discount_amount": 0,
        "tax_percentage": 16,
        "tax_amount": 160,
        "buy_tax": 0,
        "subtotal": 1000,
        "total": 1160,
        "type_price": "ST",
        "sort_order": 1
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "quote_id": 25,
  "quote_number": "QUOTE-2024-001",
  "message": "Quote created successfully"
}
```

#### 6.2 Obtener Quotes

```http
GET /api/sync-batch/quotes?company_id=1&status=draft&from_date=2024-01-01
```

**Suscripción requerida:** `sync_quotes`

**Parámetros Query:**
- `company_id` (Requerido) - integer
- `status` (Opcional) - string: draft, sent, approved, rejected, expired
- `from_date` (Opcional) - date: YYYY-MM-DD

**Ejemplo curl:**
```bash
curl -X GET "http://localhost/api/sync-batch/quotes?company_id=1&status=draft" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Respuesta:**
```json
{
  "success": true,
  "quotes": [
    {
      "id": 25,
      "quote_number": "QUOTE-2024-001",
      "customer_id": 15,
      "subtotal": 1000.00,
      "total": 1160.00,
      "status": "draft",
      "created_at": "2024-03-13T10:30:00.000000Z",
      "items": [...],
      "customer": {...},
      "seller": {...}
    }
  ]
}
```

#### 6.3 Actualizar Status de Quote

```http
PUT /api/sync-batch/quotes/{id}/status
```

**Suscripción requerida:** `sync_quotes`

**Parámetros:**
```json
{
  "company_id": 1,      // Requerido | integer
  "status": "approved"   // Requerido | string | in:pending,approved,rejected,canceled,completed
}
```

**Ejemplo curl:**
```bash
curl -X PUT http://localhost/api/sync-batch/quotes/25/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "status": "approved"
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "quote_id": 25,
  "status": "approved"
}
```

#### 6.4 Eliminar Quote

```http
DELETE /api/sync-batch/quotes/{id}
```

**Suscripción requerida:** `sync_quotes`

**Parámetros Query:**
- `company_id` (Requerido) - integer

**Ejemplo curl:**
```bash
curl -X DELETE "http://localhost/api/sync-batch/quotes/25?company_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Quote deleted successfully"
}
```

---

## ⚠️ Códigos de Error

### 401 Unauthorized
Token inválido o expirado.

### 403 Forbidden
```json
{
  "success": false,
  "message": "Tu suscripción no permite acceso a esta funcionalidad",
  "data": {
    "current_plan": "trial",
    "required_feature": "sync_quotes"
  }
}
```

### 422 Unprocessable Entity
```json
{
  "success": false,
  "message": "El número máximo de registros por lote es 5000",
  "provided": 5001,
  "max_allowed": 5000
}
```

### 500 Internal Server Error
Error del servidor - revisar logs.

---

## 📊 Límites y Restricciones

1. **Máximo de registros por lote:** 5,000
2. **Tamaño máximo del request:** 10 MB
3. **Rate limiting:** 10 requests/minute (por IP)

---

## 🔍 Ejemplos en Diferentes Lenguajes

### Python

```python
import requests

headers = {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
}

data = {
    'company_id': 1,
    'products': [
        {
            'code': 'PROD001',
            'name': 'Producto Test',
            'price': 100.00,
            'cost': 50.00,
            # ... otros campos requeridos
        }
    ]
}

response = requests.post(
    'http://localhost/api/sync-batch/products',
    headers=headers,
    json=data
)

print(response.json())
```

### JavaScript

```javascript
const headers = {
  'Authorization': 'Bearer YOUR_TOKEN',
  'Content-Type': 'application/json'
};

const data = {
  company_id: 1,
  products: [{
    code: 'PROD001',
    name: 'Producto Test',
    price: 100.00,
    cost: 50.00,
    // ... otros campos
  }]
};

fetch('http://localhost/api/sync-batch/products', {
  method: 'POST',
  headers: headers,
  body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => console.log(data));
```

### PHP

```php
$curl = curl_init();

$data = [
    'company_id' => 1,
    'products' => [
        [
            'code' => 'PROD001',
            'name' => 'Producto Test',
            'price' => 100.00,
            'cost' => 50.00,
            // ...
        ]
    ]
];

curl_setopt_array($curl, [
    CURLOPT_URL => 'http://localhost/api/sync-batch/products',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer YOUR_TOKEN',
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_CUSTOMREQUEST => 'POST'
]);

$response = curl_exec($curl);
$result = json_decode($response, true);

curl_close($curl);

print_r($result);
```

---

## 🧪 Testing

Para probar los endpoints localmente, ejecuta:

```bash
# Limpiar sesiones
docker exec -w /var/www/html/sales-apiWEB lamp-php83 php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
\$user = \App\Models\User::where('email', 'admin@test.com')->first();
if (\$user) {
    \$user->tokens()->delete();
    \App\Models\ActiveSession::where('user_id', \$user->id)->delete();
    echo 'Sesiones limpiadas' . PHP_EOL;
}
"

# Ejecutar test completo
docker exec -w /var/www/html/sales-apiWEB lamp-php83 php test_sync_controller_complete.php
```

---

**Última actualización:** 2026-03-13
**Versión:** 1.0.0
