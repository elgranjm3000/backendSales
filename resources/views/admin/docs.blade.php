@extends('layouts.admin')

@section('title', 'Documentación API - Chrystal Mobile')

@section('content')
<div x-data="{
    activeSection: 'ping',
    expandedEndpoints: {},
    toggleEndpoint(key) { this.expandedEndpoints[key] = !this.expandedEndpoints[key]; },
    isActive(section) { return this.activeSection === section; },
    scrollToSection(section) {
        this.activeSection = section;
        document.getElementById('section-' + section).scrollIntoView({ behavior: 'smooth' });
    }
}" class="p-4 md:p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Documentación API Sync-Client</h1>
        <p class="text-sm text-gray-500 mt-0.5">Endpoints de sincronización para clientes con API key</p>
    </div>

    {{-- Navegación lateral en móvil, tabs en desktop --}}
    <div class="mb-6">
        <div class="flex flex-wrap gap-2">
            <button @click="scrollToSection('ping')" :class="isActive('ping') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Ping</button>
            <button @click="scrollToSection('company')" :class="isActive('company') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Company</button>
            <button @click="scrollToSection('products')" :class="isActive('products') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Products</button>
            <button @click="scrollToSection('customers')" :class="isActive('customers') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Customers</button>
            <button @click="scrollToSection('categories')" :class="isActive('categories') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Categories</button>
            <button @click="scrollToSection('sellers')" :class="isActive('sellers') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Sellers</button>
            <button @click="scrollToSection('quotes')" :class="isActive('quotes') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">Quotes</button>
            <button @click="scrollToSection('history')" :class="isActive('history') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 transition-colors">History</button>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="space-y-6">

        {{-- PING --}}
        <div id="section-ping" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('ping')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <code class="text-sm text-gray-700">/api/sync-client/ping</code>
                </div>
                <svg :class="expandedEndpoints['ping'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['ping']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Endpoint para que el cliente verifique su conexión con el servidor.</p>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Headers</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>Authorization: Bearer {api_key}</code></pre>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Response 200</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "success": true,
    "message": "Conexión exitosa",
    "data": {
        "id": 1,
        "empresa": "Nombre Empresa",
        "rif": "J-12345678-9",
        "email": "empresa@ejemplo.com"
    }
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- COMPANY --}}
        <div id="section-company" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('company')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">POST</span>
                    <code class="text-sm text-gray-700">/api/sync-client/company/validate</code>
                </div>
                <svg :class="expandedEndpoints['company'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['company']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Valida o crea una empresa basada en su RIF y correo electrónico.</p>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Headers</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>Authorization: Bearer {api_key}</code></pre>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Body</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "rif": "J-12345678-9",
    "email": "empresa@ejemplo.com"
}</code></pre>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Response 200 (Empresa encontrada)</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "success": true,
    "company_id": 1,
    "company": {
        "id": 1,
        "name": "Nombre Empresa",
        "rif": "J-12345678-9",
        "email": "empresa@ejemplo.com"
    },
    "message": "Company validada"
}</code></pre>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Response 201 (Empresa creada)</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "success": true,
    "company_id": 2,
    "company": {
        "id": 2,
        "name": "Nueva Empresa",
        "rif": "J-98765432-1",
        "email": "nueva@ejemplo.com"
    },
    "message": "Compañia creada con exito"
}</code></pre>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Response 403 (No autorizado)</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-red-400"><code>{
    "success": false,
    "message": "El RIF o correo electrónico no están autorizados en el sistema de acceso."
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- PRODUCTS --}}
        <div id="section-products" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('products')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">POST</span>
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">DELETE</span>
                    <code class="text-sm text-gray-700">/api/sync-client/batch/products</code>
                </div>
                <svg :class="expandedEndpoints['products'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['products']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Gestión de productos por lotes.</p>

                {{-- GET --}}
                <div class="mb-6 border-l-4 border-green-200 pl-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">GET /batch/products - Obtener productos</h4>
                    <div class="mb-3">
                        <h5 class="text-xs font-semibold text-gray-500 mb-1">Query Params</h5>
                        <table class="w-full text-sm">
                            <tr class="border-b"><td class="py-1 text-gray-600">company_id</td><td class="py-1 text-gray-500">integer (required)</td></tr>
                            <tr class="border-b"><td class="py-1 text-gray-600">search</td><td class="py-1 text-gray-500">string (optional) - Busca por nombre, código o descripción</td></tr>
                            <tr class="border-b"><td class="py-1 text-gray-600">category_id</td><td class="py-1 text-gray-500">integer (optional)</td></tr>
                            <tr><td class="py-1 text-gray-600">from_date</td><td class="py-1 text-gray-500">date (optional) - Fecha de creación mínima</td></tr>
                        </table>
                    </div>
                </div>

                {{-- POST --}}
                <div class="mb-6 border-l-4 border-blue-200 pl-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">POST /batch/products - Sincronizar productos</h4>
                    <div class="mb-3">
                        <h5 class="text-xs font-semibold text-gray-500 mb-1">Body</h5>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <pre class="text-xs text-green-400"><code>{
    "company_id": 1,
    "products": [
        {
            "code": "PROD-001",
            "name": "Laptop HP",
            "description": "Laptop HP 15.6\"",
            "price": 1500.00,
            "cost": 1200.00,
            "stock": 10,
            "category_code": "Computadoras"
        }
    ]
}</code></pre>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Máximo 5000 registros por lote.</p>
                </div>

                {{-- DELETE --}}
                <div class="mb-4 border-l-4 border-red-200 pl-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">DELETE /batch/products - Eliminar productos</h4>
                    <div class="mb-3">
                        <h5 class="text-xs font-semibold text-gray-500 mb-1">Body</h5>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <pre class="text-xs text-green-400"><code>{
    "company_id": 1,
    "codes": ["PROD-001", "PROD-002"]
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CUSTOMERS --}}
        <div id="section-customers" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('customers')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">POST</span>
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">DELETE</span>
                    <code class="text-sm text-gray-700">/api/sync-client/batch/customers</code>
                </div>
                <svg :class="expandedEndpoints['customers'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['customers']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Gestión de clientes por lotes.</p>

                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">POST /batch/customers - Sincronizar clientes</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "company_id": 1,
    "customers": [
        {
            "codigo": "CLI-001",
            "name": "Juan Pérez",
            "document_number": "V-12345678",
            "email": "juan@ejemplo.com",
            "phone": "0414-1234567",
            "address": "Calle 123"
        }
    ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- CATEGORIES --}}
        <div id="section-categories" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('categories')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">POST</span>
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">DELETE</span>
                    <code class="text-sm text-gray-700">/api/sync-client/batch/categories</code>
                </div>
                <svg :class="expandedEndpoints['categories'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['categories']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Gestión de categorías por lotes.</p>

                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">POST /batch/categories - Sincronizar categorías</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "company_id": 1,
    "categories": [
        {
            "name": "Computadoras",
            "description": "Laptops y equipos de escritorio"
        },
        {
            "name": "Telefonía",
            "description": "Smartphones y accesorios"
        }
    ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- SELLERS --}}
        <div id="section-sellers" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('sellers')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">POST</span>
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">DELETE</span>
                    <code class="text-sm text-gray-700">/api/sync-client/batch/sellers</code>
                </div>
                <svg :class="expandedEndpoints['sellers'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['sellers']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Gestión de vendedores por lotes. Crea automáticamente los usuarios asociados.</p>

                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">POST /batch/sellers - Sincronizar vendedores</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "company_id": 1,
    "sellers": [
        {
            "code": "VEND-001",
            "description": "María González",
            "email": "maria@ejemplo.com",
            "password": "hashed_password_here",
            "percent_sales": 5.0,
            "percent_receivable": 2.0
        }
    ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- QUOTES --}}
        <div id="section-quotes" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('quotes')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded">POST</span>
                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs font-bold rounded">PUT</span>
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">DELETE</span>
                    <code class="text-sm text-gray-700">/api/sync-client/batch/quotes</code>
                </div>
                <svg :class="expandedEndpoints['quotes'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['quotes']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Gestión de cotizaciones creadas desde la web/app de la API.</p>

                <div class="mb-6 border-l-4 border-blue-200 pl-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">POST /batch/quotes - Crear cotización</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "company_id": 1,
    "quote_number": "COT-2024-001",
    "customer_id": 5,
    "user_seller_id": 3,
    "subtotal": 1500.00,
    "tax_amount": 180.00,
    "discount": 0,
    "discount_amount": 0,
    "total": 1680.00,
    "bcv_rate": 36.50,
    "status": "pending",
    "items": [
        {
            "product_id": 10,
            "name": "Laptop HP",
            "quantity": 1,
            "price": 1500.00,
            "unit": "pcs",
            "tax_percentage": 12,
            "tax_amount": 180.00,
            "subtotal": 1500.00,
            "total": 1680.00
        }
    ]
}</code></pre>
                    </div>
                </div>

                <div class="mb-6 border-l-4 border-yellow-200 pl-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">PUT /batch/quotes/{id}/status - Actualizar estado</h4>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-green-400"><code>{
    "status": "approved"
}</code></pre>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Estados válidos: pending, approved, rejected, canceled, completed</p>
                </div>
            </div>
        </div>

        {{-- HISTORY --}}
        <div id="section-history" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="toggleEndpoint('history')">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded">GET</span>
                    <code class="text-sm text-gray-700">/api/sync-client/batch/history</code>
                </div>
                <svg :class="expandedEndpoints['history'] ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div x-show="expandedEndpoints['history']" x-collapse class="p-4">
                <p class="text-sm text-gray-600 mb-4">Obtener historial de sincronizaciones.</p>

                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Query Params</h4>
                    <table class="w-full text-sm">
                        <tr class="border-b"><td class="py-1 text-gray-600">company_id</td><td class="py-1 text-gray-500">integer (required)</td></tr>
                        <tr class="border-b"><td class="py-1 text-gray-600">entity_type</td><td class="py-1 text-gray-500">string - products, customers, categories, sellers, quotes</td></tr>
                        <tr class="border-b"><td class="py-1 text-gray-600">from_date</td><td class="py-1 text-gray-500">date</td></tr>
                        <tr><td class="py-1 text-gray-600">status</td><td class="py-1 text-gray-500">string - completed, partial, failed</td></tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection