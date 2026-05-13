# 🔍 SYNCCONTROLLER - EXPLICACIÓN DETALLADA DE CADA ENDPOINT

## Tabla de Contenidos
1. [POST /company/validate](#1-post-companyvalidate)
2. [GET /products](#2-get-products)
3. [POST /products](#3-post-products)
4. [DELETE /products](#4-delete-products)
5. [GET /customers](#5-get-customers)
6. [POST /customers](#6-post-customers)
7. [DELETE /customers](#7-delete-customers)
8. [GET /categories](#8-get-categories)
9. [POST /categories](#9-post-categories)
10. [DELETE /categories](#10-delete-categories)
11. [GET /sellers](#11-get-sellers)
12. [POST /sellers](#12-post-sellers)
13. [DELETE /sellers](#13-delete-sellers)
14. [POST /quotes](#14-post-quotes)
15. [GET /quotes](#15-get-quotes)
16. [PUT /quotes/{id}/status](#16-put-quotesidstatus)
17. [DELETE /quotes/{id}](#17-delete-quotesid)

---

## 1. POST /company/validate

### 📌 Propósito
Valida si existe una empresa por su RIF. Si no existe, la crea automáticamente.

### 📍 URL
```
POST /api/sync-batch/company/validate
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** Activa (sin feature específico)

### 📥 Parámetros de Entrada (Request Body)

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `rif` | string | Sí | RIF de la empresa (máx 50 caracteres) | `"J123456789"` |
| `email` | email | Sí | Email de la empresa | `"empresa@test.com"` |
| `name` | string | No | Nombre de la empresa (usa RIF si no se envía) | `"Mi Empresa S.A."` |

### 📤 Parámetros de Salida (Response)

**Código 201 (Empresa Creada):**
```json
{
  "success": true,
  "company_id": 25,
  "company": {
    "id": 25,
    "name": "Mi Empresa S.A.",
    "rif": "J123456789",
    "email": "empresa@test.com"
  }
}
```

**Código 200 (Empresa Existente):**
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

### 💡 Ejemplo de Uso

```bash
curl -X POST http://localhost/api/sync-batch/company/validate \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "rif": "J123456789",
    "email": "miempresa@test.com",
    "name": "Mi Compañía S.A."
  }'
```

### 📝 Notas Importantes
- El `rif` debe ser único en toda la base de datos
- El `email` se convierte automáticamente a minúsculas
- Si no se envía `name`, usa el `rif` como nombre
- Agrega automáticamente `key_system_items_id = 1`
- No puede actualizarse una empresa existente con este endpoint

---

## 2. GET /products

### 📌 Propósito
Obtener lista de productos de una empresa con paginación, búsqueda y filtros.

### 📍 URL
```
GET /api/sync-batch/products
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_products`

### 📥 Parámetros de Entrada (Query String)

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `company_id` | integer | Sí | ID de la empresa | `1` |
| `search` | string | No | Buscar en: nombre, código, descripción | `Laptop` |
| `category_id` | integer | No | Filtrar por categoría ID | `5` |
| `from_date` | date | No | Productos creados desde esta fecha | `2024-01-01` |

### 📤 Parámetros de Salida (Response)

**Código 200 OK:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 277,
        "company_id": 1,
        "code": "LAPTOP001",
        "name": "Laptop HP 15.6\"",
        "description": "Laptop con procesador Intel i7",
        "price": "800.00",
        "cost": "600.00",
        "higher_price": "850.00",
        "coin": "USD",
        "stock": 50,
        "min_stock": 5,
        "category_id": 1,
        "status": "active",
        "created_at": "2024-03-13T10:00:00.000000Z",
        "updated_at": "2024-03-13T10:00:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost/api/sync-batch/products?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost/api/sync-batch/products?page=3",
    "links": [...],
    "next_page_url": "http://localhost/api/sync-batch/products?page=2",
    "path": "http://localhost/api/sync-batch/products",
    "per_page": 50,
    "prev_page_url": null,
    "to": 50,
    "total": 150
  }
}
```

### 💡 Ejemplos de Uso

```bash
# Todos los productos de la empresa 1
curl -X GET "http://localhost/api/sync-batch/products?company_id=1" \
  -H "Authorization: Bearer TU_TOKEN"

# Buscar productos que contengan "Laptop"
curl -X GET "http://localhost/api/sync-batch/products?company_id=1&search=Laptop" \
  -H "Authorization: Bearer TU_TOKEN"

# Productos de la categoría 5 creados desde enero 2024
curl -X GET "http://localhost/api/sync-batch/products?company_id=1&category_id=5&from_date=2024-01-01" \
  -H "Authorization: Bearer TU_TOKEN"
```

### 📝 Notas Importantes
- **Paginación:** 50 productos por página
- **Ordenamiento:** Los más recientes primero (`created_at DESC`)
- **Búsqueda:** Case insensitive (busca en mayúsculas y minúsculas)
- **Sincronización incremental:** Usa `from_date` para solo traer cambios recientes

---

## 3. POST /products

### 📌 Propósito
Sincronizar productos en lote (batch). Crea nuevos o actualiza existentes (UPSERT).

### 📍 URL
```
POST /api/sync-batch/products
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_products`

### 📥 Parámetros de Entrada (Request Body)

```json
{
  "company_id": 1,
  "products": [
    {
      "code": "PROD001",
      "name": "Laptop HP 15.6\"",
      "description": "Laptop con procesador Intel i7, 16GB RAM",
      "price": 800.00,
      "cost": 600.00,
      "higher_price": 850.00,
      "coin": "USD",
      "description_coin": "Dólares americanos",
      "stock": 50,
      "min_stock": 5,
      "category_id": 1,
      "status": "active",
      "weight": 2.5,
      "unitary_cost": 600.00,
      "buy_tax": "0",
      "buy_aliquot": 0.0,
      "sale_tax": "16",
      "aliquot": 16.0
    }
  ]
}
```

### Desglose de Campos

#### Campos de Identificación
- **`code`** (string, 50 chars, REQUIRED) - Código único del producto. Si existe, actualiza; si no, crea.
- **`name`** (string, 255 chars, REQUIRED) - Nombre del producto
- **`description`** (string, OPTIONAL) - Descripción detallada

#### Campos de Precios (Todos REQUIRED)
- **`price`** (numeric) - Precio de venta actual
- **`cost`** (numeric) - Costo del producto
- **`higher_price`** (numeric) - Precio más alto histórico

#### Campos de Moneda (Todos REQUIRED)
- **`coin`** (string, 10 chars) - Moneda: "USD", "EUR", "VES"
- **`description_coin`** (string) - Descripción: "Dólares", "Euros"

#### Campos de Inventario (Todos REQUIRED)
- **`stock`** (numeric) - Cantidad actual en stock
- **`min_stock`** (numeric) - Stock mínimo para alerta

#### Campos de Categorización
- **`category_id`** (integer, REQUIRED) - ID de la categoría

#### Campos de Impuestos (Todos REQUIRED)
- **`buy_tax`** (string) - Impuesto de compra: "0", "exento"
- **`buy_aliquot`** (numeric) - Alicuota de compra: 0.0, 16.0
- **`sale_tax`** (string) - Impuesto de venta: "16", "8"
- **`aliquot`** (numeric) - Alicuota de venta: 16.0, 8.0

#### Campos Adicionales
- **`status`** (string, OPTIONAL) - "active" o "inactive"
- **`weight`** (numeric, REQUIRED) - Peso en kg
- **`unitary_cost`** (numeric, REQUIRED) - Costo unitario

### 📤 Parámetros de Salida (Response)

**Código 200 OK:**
```json
{
  "success": true,
  "created": 8,
  "updated": 2,
  "errors": 0,
  "error_details": []
}
```

**Código 200 con Errores:**
```json
{
  "success": true,
  "created": 8,
  "updated": 1,
  "errors": 1,
  "error_details": [
    {
      "index": 5,
      "key": "PROD006",
      "error": "SQLSTATE[23000]: Duplicate entry 'PROD006' for key 'products_code_unique'"
    }
  ]
}
```

**Código 422 - Límite Excedido:**
```json
{
  "success": false,
  "message": "El número máximo de registros por lote es 5000",
  "provided": 6000,
  "max_allowed": 5000
}
```

### 💡 Ejemplo de Uso

```bash
curl -X POST http://localhost/api/sync-batch/products \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "products": [
      {
        "code": "LAPTOP-HP-001",
        "name": "Laptop HP 15.6\"",
        "description": "Intel i7, 16GB RAM, 512GB SSD",
        "price": 800,
        "cost": 600,
        "higher_price": 850,
        "coin": "USD",
        "description_coin": "Dólares",
        "stock": 50,
        "min_stock": 5,
        "category_id": 1,
        "status": "active",
        "weight": 2.5,
        "unitary_cost": 600,
        "buy_tax": "0",
        "buy_aliquot": 0,
        "sale_tax": "16",
        "aliquot": 16
      },
      {
        "code": "MOUSE-LOG-001",
        "name": "Mouse Logitech",
        "description": "Mouse inalámbrico",
        "price": 25,
        "cost": 15,
        "higher_price": 30,
        "coin": "USD",
        "description_coin": "Dólares",
        "stock": 200,
        "min_stock": 20,
        "category_id": 1,
        "status": "active",
        "weight": 0.2,
        "unitary_cost": 15,
        "buy_tax": "0",
        "buy_aliquot": 0,
        "sale_tax": "16",
        "aliquot": 16
      }
    ]
  }'
```

### 📝 Notas Importantes
- ⚠️ **Máximo 5,000 productos** por request
- 🔄 **UPSERT automático:** Si el `code` existe → actualiza; si no → crea
- ✅ **Transaccional:** Todo o nada (DB transaction)
- 🛡️ **Try-catch individual:** Si un producto falla, los otros continúan
- 📊 **Estadísticas:** Retorna cuántos creó, actualizó y cuántos errores

---

## 4. DELETE /products

### 📌 Propósito
Eliminar múltiples productos de una empresa por sus códigos.

### 📍 URL
```
DELETE /api/sync-batch/products
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_products`

### 📥 Parámetros de Entrada (Request Body)

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `company_id` | integer | Sí | ID de la empresa | `1` |
| `codes` | array | Sí | Lista de códigos de productos a eliminar | `["PROD001", "PROD002"]` |

### 📤 Parámetros de Salida (Response)

**Código 200 OK:**
```json
{
  "success": true,
  "deleted": 2
}
```

### 💡 Ejemplo de Uso

```bash
curl -X DELETE http://localhost/api/sync-batch/products \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "codes": ["PROD001", "PROD002", "LAPTOP-HP-001"]
  }'
```

### 📝 Notas Importantes
- ⚠️ **Eliminación permanente:** No se puede deshacer
- ✅ Solo elimina productos de la `company_id` especificada
- 🔄 Retorna cantidad de productos eliminados (puede ser 0 si no existen)

---

## 5. GET /customers

### 📌 Propósito
Obtener lista de clientes de una empresa con paginación y búsqueda.

### 📍 URL
```
GET /api/sync-batch/customers
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_customers`

### 📥 Parámetros de Entrada (Query String)

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `company_id` | integer | Sí | ID de la empresa | `1` |
| `search` | string | No | Buscar en: nombre, documento, email | `Juan` |
| `from_date` | date | No | Clientes creados desde | `2024-01-01` |

### 📤 Parámetros de Salida (Response)

**Código 200 OK:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 45,
        "company_id": 1,
        "document_number": "V12345678",
        "name": "Juan Pérez",
        "email": "juan@test.com",
        "phone": "+58-414-1234567",
        "address": "Calle 123, Urbanización",
        "status": "active",
        "created_at": "2024-03-13T10:00:00.000000Z",
        "updated_at": "2024-03-13T10:00:00.000000Z"
      }
    ],
    "per_page": 50,
    "total": 150
  }
}
```

### 💡 Ejemplo de Uso

```bash
# Todos los clientes
curl -X GET "http://localhost/api/sync-batch/customers?company_id=1" \
  -H "Authorization: Bearer TU_TOKEN"

# Buscar clientes con nombre "Juan"
curl -X GET "http://localhost/api/sync-batch/customers?company_id=1&search=Juan" \
  -H "Authorization: Bearer TU_TOKEN"
```

---

## 6. POST /customers

### 📌 Propósito
Sincronizar clientes en lote. Crea nuevos o actualiza existentes (UPSERT).

### 📍 URL
```
POST /api/sync-batch/customers
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_customers`

### 📥 Parámetros de Entrada (Request Body)

```json
{
  "company_id": 1,
  "customers": [
    {
      "document_number": "V12345678",
      "name": "Juan Pérez",
      "email": "juanperez@test.com",
      "phone": "+58-414-1234567",
      "address": "Avenida Principal #123, Piso 2",
      "status": "active"
    }
  ]
}
```

### Desglose de Campos

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `document_number` | string, 50 chars | SÍ | Documento de identidad (único por empresa) | `"V12345678"` |
| `name` | string, 255 chars | SÍ | Nombre completo del cliente | `"Juan Pérez"` |
| `email` | email | No | Email del cliente | `"juan@test.com"` |
| `phone` | string, 20 chars | No | Teléfono | `"+58-414-1234567"` |
| `address` | string | No | Dirección física | `"Calle 123"` |
| `status` | string | No | Estado: active, inactive | `"active"` |

### 📤 Parámetros de Salida (Response)

Igual que POST /products (created, updated, errors)

### 💡 Ejemplo de Uso

```bash
curl -X POST http://localhost/api/sync-batch/customers \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "customers": [
      {
        "document_number": "V12345678",
        "name": "María González",
        "email": "maria.g@test.com",
        "phone": "+58-424-9876543",
        "address": "Calle 45, Urbanización El Rosal"
      },
      {
        "document_number": "V87654321",
        "name": "Carlos Rodríguez",
        "email": "carlosr@test.com",
        "phone": "+58-212-1234567"
      }
    ]
  }'
```

---

## 7. DELETE /customers

### 📌 Propósito
Eliminar múltiples clientes por su número de documento.

### 📍 URL
```
DELETE /api/sync-batch/customers
```

### 📥 Parámetros de Entrada

```json
{
  "company_id": 1,
  "documents": ["V12345678", "V87654321"]
}
```

### 💡 Ejemplo de Uso

```bash
curl -X DELETE http://localhost/api/sync-batch/customers \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "documents": ["V12345678", "V87654321", "V11112222"]
  }'
```

---

## 8. GET /categories

### 📌 Propósito
Obener lista de categorías de una empresa.

### 📍 URL
```
GET /api/sync-batch/categories
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_categories`

### 📥 Parámetros de Entrada (Query String)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `company_id` | integer | Sí | ID de la empresa |
| `search` | string | No | Buscar por nombre |
| `from_date` | date | No | Categorías creadas desde |

### 📤 Parámetros de Salida

**Orden:** Alfabético por nombre (A-Z)

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "company_id": 1,
        "name": "Alimentos",
        "description": "Productos alimenticios",
        "status": "active"
      },
      {
        "id": 1,
        "company_id": 1,
        "name": "Electrónica",
        "description": "Productos electrónicos",
        "status": "active"
      }
    ],
    "per_page": 50,
    "total": 17
  }
}
```

### 💡 Ejemplo de Uso

```bash
# Todas las categorías
curl -X GET "http://localhost/api/sync-batch/categories?company_id=1" \
  -H "Authorization: Bearer TU_TOKEN"

# Buscar categorías con "Electro"
curl -X GET "http://localhost/api/sync-batch/categories?company_id=1&search=Electro" \
  -H "Authorization: Bearer TU_TOKEN"
```

---

## 9. POST /categories

### 📌 Propósito
Sincronizar categorías en lote.

### 📍 URL
```
POST /api/sync-batch/categories
```

### 📥 Parámetros de Entrada

```json
{
  "company_id": 1,
  "categories": [
    {
      "name": "Electrónica",
      "description": "Productos electrónicos y computación",
      "status": "active"
    }
  ]
}
```

### Campos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `name` | string, 255 chars | SÍ | Nombre único por empresa |
| `description` | string | No | Descripción de la categoría |
| `status` | string | No | "active" o "inactive" |

### 💡 Ejemplo de Uso

```bash
curl -X POST http://localhost/api/sync-batch/categories \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "categories": [
      {
        "name": "Ropa y Accesorios",
        "description": "Prendas de vestir y accesorios",
        "status": "active"
      },
      {
        "name": "Hogar",
        "description": "Artículos para el hogar"
      }
    ]
  }'
```

---

## 10. DELETE /categories

### 📌 Propósito
Eliminar múltiples categorías por su nombre.

### 📍 URL
```
DELETE /api/sync-batch/categories
```

### 📥 Parámetros de Entrada

```json
{
  "company_id": 1,
  "names": ["Electrónica", "Ropa"]
}
```

### 💡 Ejemplo de Uso

```bash
curl -X DELETE http://localhost/api/sync-batch/categories \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "names": ["Categoría Vieja", "Otra Categoría"]
  }'
```

---

## 11. GET /sellers

### 📌 Propósito
Obtener lista de vendedores de una empresa con sus usuarios asociados.

### 📍 URL
```
GET /api/sync-batch/sellers
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_sellers`

### 📥 Parámetros de Entrada (Query String)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `company_id` | integer | Sí | ID de la empresa |
| `search` | string | No | Buscar en: código, nombre, email |
| `from_date` | date | No | Vendedores creados desde |

### 📤 Parámetros de Salida

**Incluye relación con User:**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 12,
        "company_id": 1,
        "code": "SELLER01",
        "status": "active",
        "created_at": "2024-03-13T10:00:00.000000Z",
        "user": {
          "id": 8,
          "name": "Juan Pérez",
          "email": "juanp@company.com"
        }
      }
    ],
    "per_page": 50,
    "total": 15
  }
}
```

### 💡 Ejemplo de Uso

```bash
# Todos los vendedores
curl -X GET "http://localhost/api/sync-batch/sellers?company_id=1" \
  -H "Authorization: Bearer TU_TOKEN"

# Buscar vendedores con "Juan"
curl -X GET "http://localhost/api/sync-batch/sellers?company_id=1&search=Juan" \
  -H "Authorization: Bearer TU_TOKEN"
```

---

## 12. POST /sellers

### 📌 Propósito
Sincronizar vendedores en lote. Crea el vendedor Y el usuario asociado.

### 📍 URL
```
POST /api/sync-batch/sellers
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_sellers`

### 📥 Parámetros de Entrada

```json
{
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
}
```

### Campos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `code` | string, 50 chars | SÍ | Código único + company_id |
| `description` | string, 255 chars | SÍ | Nombre completo |
| `email` | email | SÍ | Email del usuario (único global) |
| `password` | string | SÍ | Password hasheado con bcrypt |
| `status` | string | No | "active" o "inactive" |

### ⚠️ IMPORTANTE: Password

El password DEBE venir hasheado. Ejemplos:

**PHP:**
```php
$hashedPassword = bcrypt('password123');
// Resultado: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

**Python:**
```python
import bcrypt
hashed = bcrypt.hashpw(b'password123', bcrypt.gensalt()).decode('utf-8')
```

**JavaScript (Node):**
```javascript
const bcrypt = require('bcrypt');
const hashed = bcrypt.hashSync('password123', 10);
```

### 📤 Parámetros de Salida

```json
{
  "success": true,
  "created": 2,
  "updated": 0,
  "errors": 0,
  "error_details": []
}
```

### 📝 Comportamiento Automático

1. Busca si existe un User con el email
2. Si no existe → Crea User con:
   - `name` = `description`
   - `email` = `email`
   - `password` = `password` (hasheado)
   - `role` = `seller`
   - `status` = `active`
3. Crea o actualiza Seller con `user_id` asociado

### 💡 Ejemplo de Uso

```bash
curl -X POST http://localhost/api/sync-batch/sellers \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "sellers": [
      {
        "code": "SELLER_JUAN",
        "description": "Juan Pérez",
        "email": "juan.perez@miempresa.com",
        "password": "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi"
      }
    ]
  }'
```

---

## 13. DELETE /sellers

### 📌 Propósito
Eliminar múltiples vendedores por su código.

### 📍 URL
```
DELETE /api/sync-batch/sellers
```

### 📥 Parámetros de Entrada

```json
{
  "company_id": 1,
  "codes": ["SELLER01", "SELLER02"]
}
```

### 💡 Ejemplo de Uso

```bash
curl -X DELETE http://localhost/api/sync-batch/sellers \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "codes": ["SELLER_JUAN", "SELLER_MARIA"]
  }'
```

---

## 14. POST /quotes

### 📌 Propósito
Crear una cotización (quote) con sus items de productos.

### 📍 URL
```
POST /api/sync-batch/quotes
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_quotes` ⚠️ **NO disponible en plan trial**

### 📥 Parámetros de Entrada

```json
{
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
      "name": "Laptop HP 15.6\"",
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
}
```

### Campos del Quote (Encabezado)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `company_id` | integer | Sí | ID de la empresa |
| `quote_number` | string, 50 chars | SÍ | Número de cotización (único) |
| `customer_id` | integer | Sí | ID del cliente |
| `user_seller_id` | integer | No | ID del vendedor |
| `subtotal` | numeric | Sí | Subtotal sin impuestos |
| `tax_amount` | numeric | Sí | Monto de impuestos |
| `discount` | numeric | No | Porcentaje de descuento |
| `discount_amount` | numeric | No | Monto de descuento |
| `total` | numeric | Sí | Total final |
| `bcv_rate` | numeric | No | Tasa de cambio BCV |
| `status` | string | Sí | **Estado válido (ver abajo)** |
| `items` | array | Sí | Items de la cotización |

### Estados Válidos (enum QuoteStatus)

- `draft` - Borrador
- `sent` - Enviado al cliente
- `approved` - Aprobado
- `rejected` - Rechazado
- `expired` - Expirado

⚠️ **NO usar** `pending` - No existe en el enum

### Campos de Items

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `product_id` | integer | Sí | ID del producto |
| `name` | string | No | Nombre del producto |
| `item_type` | string | No | Tipo: "product" |
| `unit` | string | No | Unidad: "pcs", "kg", "m" |
| `quantity` | numeric | Sí | Cantidad (mínimo 1) |
| `price` | numeric | Sí | Precio unitario → se mapea a `unit_price` |
| `discount_percentage` | numeric | No | Porcentaje de descuento |
| `discount_amount` | numeric | No | Monto de descuento |
| `tax_percentage` | numeric | No | Porcentaje de impuesto |
| `tax_amount` | numeric | No | Monto de impuesto |
| `buy_tax` | integer | No | Impuesto de compra |
| `subtotal` | numeric | No | Subtotal del item |
| `total` | numeric | No | Total del item |
| `type_price` | string, 2 chars | No | Tipo de precio (max 2 caracteres) |
| `sort_order` | integer | No | Orden de visualización |

### 📤 Parámetros de Salida

**Código 201 Created:**
```json
{
  "success": true,
  "quote_id": 25,
  "quote_number": "QUOTE-2024-001",
  "message": "Quote created successfully"
}
```

### 💡 Ejemplo de Uso

```bash
curl -X POST http://localhost/api/sync-batch/quotes \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "quote_number": "COT-2024-03-13-001",
    "customer_id": 15,
    "user_seller_id": 5,
    "subtotal": 1000,
    "tax_amount": 160,
    "discount": 0,
    "discount_amount": 0,
    "total": 1160,
    "bcv_rate": 35.5,
    "status": "draft",
    "items": [
      {
        "product_id": 10,
        "name": "Laptop HP",
        "item_type": "product",
        "unit": "pcs",
        "quantity": 2,
        "price": 500,
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

### 📝 Notas Importantes
- ⚠️ **NO disponible en plan trial** - Necesitas plan Monthly o superior
- ✅ Crea automáticamente los items del quote
- ✅ Calcula totales automáticamente si no se envían
- ⚠️ `type_price` máximo 2 caracteres: "ST", "PR", "WH", etc.

---

## 15. GET /quotes

### 📌 Propósito
Obener cotizaciones de una empresa con filtros.

### 📍 URL
```
GET /api/sync-batch/quotes
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_quotes`

### 📥 Parámetros de Entrada (Query String)

| Campo | Tipo | Requerido | Descripción | Valores Válidos |
|-------|------|-----------|-------------|----------------|
| `company_id` | integer | Sí | ID de la empresa | `1` |
| `status` | string | No | Filtrar por estado | `draft`, `sent`, `approved`, `rejected`, `expired` |
| `from_date` | date | No | Quotes creados desde | `2024-01-01` |

### 📤 Parámetros de Salida

```json
{
  "success": true,
  "quotes": [
    {
      "id": 25,
      "company_id": 1,
      "quote_number": "COT-2024-03-13-001",
      "customer_id": 15,
      "user_seller_id": 5,
      "subtotal": "1000.00",
      "tax_amount": "160.00",
      "total": "1160.00",
      "bcv_rate": "35.50",
      "status": "draft",
      "created_at": "2024-03-13T10:30:00.000000Z",
      "updated_at": "2024-03-13T10:30:00.000000Z",
      "items": [
        {
          "id": 40,
          "quote_id": 25,
          "product_id": 10,
          "name": "Laptop HP",
          "quantity": "2.000",
          "unit_price": "500.00",
          "subtotal": "1000.00",
          "total": "1160.00"
        }
      ],
      "customer": {
        "id": 15,
        "name": "Juan Pérez",
        "email": "juan@test.com"
      },
      "seller": {
        "id": 5,
        "name": "María González"
      }
    }
  ]
}
```

### 💡 Ejemplos de Uso

```bash
# Todas las cotizaciones
curl -X GET "http://localhost/api/sync-batch/quotes?company_id=1" \
  -H "Authorization: Bearer TU_TOKEN"

# Solo cotizaciones en borrador
curl -X GET "http://localhost/api/sync-batch/quotes?company_id=1&status=draft" \
  -H "Authorization: Bearer TU_TOKEN"

# Cotizaciones aprobadas desde enero 2024
curl -X GET "http://localhost/api/sync-batch/quotes?company_id=1&status=approved&from_date=2024-01-01" \
  -H "Authorization: Bearer TU_TOKEN"
```

---

## 16. PUT /quotes/{id}/status

### 📌 Propósito
Actualizar el estado de una cotización.

### 📍 URL
```
PUT /api/sync-batch/quotes/{id}/status
```

Donde `{id}` es el ID del quote (ejemplo: `/api/sync-batch/quotes/25/status`)

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_quotes`

### 📥 Parámetros de Entrada (Request Body)

| Campo | Tipo | Requerido | Descripción | Valores Válidos |
|-------|------|-----------|-------------|----------------|
| `company_id` | integer | Sí | ID de la empresa | `1` |
| `status` | string | Sí | Nuevo estado | `draft`, `sent`, `approved`, `rejected`, `expired`, `canceled`, `completed` |

### 📤 Parámetros de Salida

**Código 200 OK:**
```json
{
  "success": true,
  "quote_id": 25,
  "status": "approved"
}
```

**Código 404 Not Found:**
```json
{
  "success": false,
  "error": "Quote not found"
}
```

### 💡 Ejemplos de Uso

```bash
# Aprobar cotización
curl -X PUT http://localhost/api/sync-batch/quotes/25/status \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "status": "approved"
  }'

# Rechazar cotización
curl -X PUT http://localhost/api/sync-batch/quotes/25/status \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "status": "rejected"
  }'
```

---

## 17. DELETE /quotes/{id}

### 📌 Propósito
Eliminar una cotización y todos sus items.

### 📍 URL
```
DELETE /api/sync-batch/quotes/{id}
```

### 🔐 Autenticación
- **Requiere:** Token Bearer
- **Suscripción:** `sync_quotes`

### 📥 Parámetros de Entrada (Query String)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `company_id` | integer | Sí | ID de la empresa |

### 📤 Parámetros de Salida

**Código 200 OK:**
```json
{
  "success": true,
  "message": "Quote deleted successfully"
}
```

**Código 404 Not Found:**
```json
{
  "success": false,
  "error": "Quote not found"
}
```

### 💡 Ejemplo de Uso

```bash
curl -X DELETE "http://localhost/api/sync-batch/quotes/25?company_id=1" \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### 📝 Notas Importantes
- ⚠️ **Elimina permanentemente** el quote Y todos sus items
- ⚠️ No se puede deshacer
- ✅ Verifica que el quote pertenezca a la company_id antes de eliminar

---

## 📊 RESUMEN FINAL DE ENDPOINTS

| # | Método | Endpoint | Propósito |
|---|--------|----------|-----------|
| 1 | POST | `/company/validate` | Validar/crear empresa |
| 2 | GET | `/products` | Listar productos (paginado, búsqueda) |
| 3 | POST | `/products` | Crear/actualizar productos (batch) |
| 4 | DELETE | `/products` | Eliminar productos (batch) |
| 5 | GET | `/customers` | Listar clientes (paginado, búsqueda) |
| 6 | POST | `/customers` | Crear/actualizar clientes (batch) |
| 7 | DELETE | `/customers` | Eliminar clientes (batch) |
| 8 | GET | `/categories` | Listar categorías (paginado) |
| 9 | POST | `/categories` | Crear/actualizar categorías (batch) |
| 10 | DELETE | `/categories` | Eliminar categorías (batch) |
| 11 | GET | `/sellers` | Listar vendedores (paginado, búsqueda) |
| 12 | POST | `/sellers` | Crear/actualizar vendedores (batch) |
| 13 | DELETE | `/sellers` | Eliminar vendedores (batch) |
| 14 | POST | `/quotes` | Crear cotización con items |
| 15 | GET | `/quotes` | Listar cotizaciones (filtros) |
| 16 | PUT | `/quotes/{id}/status` | Cambiar estado de cotización |
| 17 | DELETE | `/quotes/{id}` | Eliminar cotización |

---

## 🎓 **CONCEPTOS CLAVE**

### **UPSERT**
En los endpoints POST con batch (products, customers, categories, sellers):
- Si el registro **existe** → **ACTUALIZA**
- Si el registro **NO existe** → **CREA**
- La clave única es: `key_field` + `company_id`

### **Batch Sync**
- Puedes enviar hasta **5,000 registros** en un solo request
- El sistema procesa cada registro individualmente
- Si un registro falla, los otros continúan
- Retorna estadísticas detalladas

### **Paginación**
- Todos los endpoints GET retornan **50 registros por página**
- Incluyen metadata: `current_page`, `total`, `last_page`, etc.

### **Transacciones**
- Todas las operaciones de escritura (POST, PUT, DELETE) usan **DB Transactions**
- Todo o nada: si falla, hace rollback de todo

---

## 🚀 **FLUJO DE TRABAJO RECOMENDADO**

1. **Login** → Obtener token
2. **Validar Empresa** → `POST /company/validate`
3. **Sincronizar Categorías** → `POST /categories`
4. **Sincronizar Productos** → `POST /products`
5. **Sincronizar Clientes** → `POST /customers`
6. **Sincronizar Vendedores** → `POST /sellers`
7. **Crear Cotizaciones** → `POST /quotes`

---

## 📞 **¿NECESITAS MÁS AYUDA?**

Para dudas específicas sobre algún endpoint, revisa:
- Documentación técnica completa: `SYNC_CONTROLLER_DOCUMENTACION_COMPLETA.md`
- Guía rápida de API: `SYNC_CONTROLLER_API.md`
- Tests completos: `test_sync_controller_complete.php`

---

**Última actualización:** 2026-03-13
**Versión:** 1.0.0
