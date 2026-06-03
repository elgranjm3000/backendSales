@extends('layouts.admin')

@section('title', 'Documentación API - Chrystal Mobile')

@section('content')
<script>
function docsData() {
    return {
        activeSection: 'ping',
        sections: [
            { id: 'ping', label: 'Ping' },
            { id: 'company', label: 'Company' },
            { id: 'products', label: 'Products' },
            { id: 'customers', label: 'Customers' },
            { id: 'categories', label: 'Categories' },
            { id: 'sellers', label: 'Sellers' },
            { id: 'quotes', label: 'Quotes' },
            { id: 'history', label: 'History' },
            { id: 'lastsync', label: 'Last Sync' },
        ],
        isOpen(section) {
            return this.activeSection === section;
        },
        toggle(section) {
            this.activeSection = this.activeSection === section ? null : section;
        },
        scrollTo(section) {
            this.activeSection = section;
            document.getElementById('endpoint-' + section)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
}
</script>
<div x-data="docsData()" class="p-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Documentación API Sync</h1>
        <p class="text-sm text-gray-500 mt-1">Endpoints de sincronización para clientes con API key</p>
        <div class="mt-3 inline-flex items-center gap-2 bg-gray-900 rounded-lg px-4 py-2.5">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Base URL</span>
            <code class="text-sm text-gray-100">{{ config('app.url') }}/api/sync-client</code>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex flex-wrap gap-2 mb-8 border-b border-gray-200 pb-4">
        <template x-for="section in sections" :key="section.id">
            <button @click="scrollTo(section.id)"
                    :class="activeSection === section.id
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-600 hover:bg-gray-100 border-gray-200'"
                    class="px-3.5 py-2 rounded-lg text-sm font-medium border transition-colors">
                <span x-text="section.label"></span>
            </button>
        </template>
    </nav>

    {{-- Cards --}}
    <div class="space-y-5">

        {{-- ========================================================================= --}}
        {{-- PING --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-ping" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('ping')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/ping</code>
                </div>
                <svg :class="isOpen('ping') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('ping')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">
                        <p class="text-sm text-gray-600">Verifica que la conexión entre el cliente y el servidor funciona correctamente. Retorna los datos de la empresa asociada a la API key.</p>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Headers</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>Authorization: Bearer {api_key}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response <span class="text-emerald-500">200 OK</span></h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "message": "Conexión exitosa",
    "data": {
        "id": 1,
        "empresa": "Nombre de la Empresa",
        "rif": "J-12345678-9",
        "email": "empresa@ejemplo.com"
    }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- COMPANY --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-company" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('company')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POST</span>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/company/validate</code>
                </div>
                <svg :class="isOpen('company') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('company')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">
                        <p class="text-sm text-gray-600">Valida o crea una empresa a partir de su RIF y correo electrónico. Si la empresa ya existe retorna sus datos; si no, la crea automáticamente en el sistema.</p>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Headers</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>Authorization: Bearer {api_key}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Body</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>{
    "rif": "J-12345678-9",
    "email": "empresa@ejemplo.com"
}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response <span class="text-emerald-500">200 OK</span> — Empresa existente</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "company_id": 1,
    "company": {
        "id": 1,
        "name": "Nombre de la Empresa",
        "rif": "J-12345678-9",
        "email": "empresa@ejemplo.com"
    },
    "message": "Company validada"
}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response <span class="text-emerald-500">201 Created</span> — Empresa nueva</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>{
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

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response <span class="text-red-500">403 Forbidden</span></h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-red-300"><code>{
    "success": false,
    "message": "El RIF o correo electrónico no están autorizados en el sistema de acceso."
}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response <span class="text-red-500">422 Unprocessable</span></h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-red-300"><code>{
    "message": "El campo rif es requerido. (and 1 more error)",
    "errors": {
        "rif": ["El campo rif es requerido."],
        "email": ["El campo email es requerido."]
    }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- PRODUCTS --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-products" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('products')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POST</span>
                    </div>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/products</code>
                </div>
                <svg :class="isOpen('products') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('products')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">

                        {{-- GET --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">GET</span>
                                Obtener productos
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                            <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                            <td class="py-2">ID de la empresa</td>
                                        </tr>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">search</code></td>
                                            <td class="py-2">Buscar por nombre, código o descripción</td>
                                        </tr>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">category_id</code></td>
                                            <td class="py-2">Filtrar por ID de categoría</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">from_date</code></td>
                                            <td class="py-2">Fecha de creación mínima (Y-m-d)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div>
                                    <p class="text-xs text-gray-500 mb-2">Paginación: 50 registros por página.</p>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "company_id": 1,
                "code": "PROD001",
                "name": "Laptop HP",
                "description": "Laptop HP 15.6\"",
                "price": 1500.00,
                "cost": 1200.00,
                "stock": 10,
                "category_id": 1,
                "created_at": "2024-01-15T10:00:00.000000Z",
                "updated_at": "2024-01-15T10:00:00.000000Z"
            }
        ],
        "total": 150,
        "per_page": 50,
        "last_page": 3
    }
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- POST --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">POST</span>
                                Sincronizar productos
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <p class="text-xs text-gray-500">Crea o actualiza productos. Si el <code class="text-xs bg-gray-100 px-1">code</code> ya existe para la empresa, se actualiza; si no, se crea. Máximo <strong>5.000 registros</strong> por lote.</p>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Body</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "company_id": 1,
    "products": [
        {
            "code": "PROD001",
            "name": "Laptop HP",
            "description": "Laptop HP 15.6\"",
            "price": 1500.00,
            "cost": 1200.00,
            "stock": 10,
            "category_id": "Computadoras",
            "product_image": "iVBORw0KGgo...",   // base64 (opcional)
            "image_type": "image/png"               // (opcional)
        }
    ]
}</code></pre>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1.5">Nota: <code>category_id</code> acepta el nombre de la categoría, no el ID numérico.</p>
                                </div>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Response</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "created": 5,
    "updated": 3,
    "errors": 0,
    "error_details": [],
    "synced_at": "2024-01-15T10:30:00+00:00"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- CUSTOMERS --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-customers" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('customers')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POST</span>
                    </div>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/customers</code>
                </div>
                <svg :class="isOpen('customers') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('customers')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">

                        {{-- GET --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">GET</span>
                                Obtener clientes
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                            <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                            <td class="py-2">ID de la empresa</td>
                                        </tr>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">search</code></td>
                                            <td class="py-2">Buscar por nombre, documento o email</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">from_date</code></td>
                                            <td class="py-2">Fecha de creación mínima (Y-m-d)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-xs text-gray-500">Paginación: 50 registros por página.</p>
                            </div>
                        </div>

                        {{-- POST --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">POST</span>
                                Sincronizar clientes
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <p class="text-xs text-gray-500">Crea o actualiza clientes usando <code>codigo</code> como clave única por empresa. Máximo <strong>5.000 registros</strong> por lote.</p>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Body</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "company_id": 1,
    "customers": [
        {
            "codigo": "CLI-001",
            "name": "Juan Pérez",
            "document_number": "V-12345678",
            "email": "juan@ejemplo.com",
            "phone": "0414-1234567",
            "address": "Calle 123, Ciudad"
        }
    ]
}</code></pre>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Response</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "created": 10,
    "updated": 2,
    "errors": 0,
    "error_details": [],
    "synced_at": "2024-01-15T10:30:00+00:00"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- CATEGORIES --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-categories" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('categories')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POST</span>
                    </div>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/categories</code>
                </div>
                <svg :class="isOpen('categories') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('categories')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">

                        {{-- GET --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">GET</span>
                                Obtener categorías
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                            <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                            <td class="py-2">ID de la empresa</td>
                                        </tr>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">search</code></td>
                                            <td class="py-2">Buscar por nombre</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">from_date</code></td>
                                            <td class="py-2">Fecha de creación mínima (Y-m-d)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-xs text-gray-500">Paginación: 50 registros por página. Ordenadas alfabéticamente.</p>
                            </div>
                        </div>

                        {{-- POST --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">POST</span>
                                Sincronizar categorías
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <p class="text-xs text-gray-500">Crea o actualiza categorías usando <code>name</code> como clave única por empresa. Máximo <strong>5.000 registros</strong> por lote.</p>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Body</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
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
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Response</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "created": 2,
    "updated": 0,
    "errors": 0,
    "error_details": [],
    "synced_at": "2024-01-15T10:30:00+00:00"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- SELLERS --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-sellers" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('sellers')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POST</span>
                    </div>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/sellers</code>
                </div>
                <svg :class="isOpen('sellers') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('sellers')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">

                        {{-- GET --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">GET</span>
                                Obtener vendedores
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                            <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                            <td class="py-2">ID de la empresa</td>
                                        </tr>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">search</code></td>
                                            <td class="py-2">Buscar por código, nombre o email del vendedor</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">from_date</code></td>
                                            <td class="py-2">Fecha de creación mínima (Y-m-d)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-xs text-gray-500">Paginación: 50 registros por página. Incluye datos del usuario asociado.</p>
                            </div>
                        </div>

                        {{-- POST --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">POST</span>
                                Sincronizar vendedores
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <p class="text-xs text-gray-500">Crea o actualiza vendedores usando <code>code</code> como clave única por empresa. Crea automáticamente los usuarios asociados. La contraseña debe venir ya hasheada desde el cliente. Máximo <strong>5.000 registros</strong> por lote.</p>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Body</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "company_id": 1,
    "sellers": [
        {
            "code": "VEND001",
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
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Response</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "created": 3,
    "updated": 1,
    "errors": 0,
    "error_details": [],
    "synced_at": "2024-01-15T10:30:00+00:00"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- QUOTES --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-quotes" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('quotes')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POST</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">PUT</span>
                    </div>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/quotes</code>
                </div>
                <svg :class="isOpen('quotes') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('quotes')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">

                        {{-- GET --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">GET</span>
                                Obtener cotizaciones
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                            <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600">
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                            <td class="py-2">ID de la empresa</td>
                                        </tr>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">status</code></td>
                                            <td class="py-2">Filtrar por estado (pending, approved, rejected, canceled, completed)</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">from_date</code></td>
                                            <td class="py-2">Fecha de creación mínima (Y-m-d)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-xs text-gray-500">Incluye items, cliente y vendedor asociados.</p>
                            </div>
                        </div>

                        {{-- POST --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">POST</span>
                                Crear cotización
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <p class="text-xs text-gray-500">Crea una cotización con sus items. Las cotizaciones se crean desde la web/app del cliente, no vienen de PostgreSQL.</p>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Body</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
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
            "unit": "pcs",
            "price": 1500.00,
            "tax_percentage": 12,
            "tax_amount": 180.00,
            "subtotal": 1500.00,
            "total": 1680.00,
            "discount_percentage": 0,
            "discount_amount": 0,
            "type_price": "standard",
            "sort_order": 0
        }
    ]
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- PUT --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">PUT</span>
                                Actualizar estado de cotización
                            </h4>
                            <div class="border-l-2 border-gray-200 pl-4 space-y-4">
                                <p class="text-xs text-gray-500">Cambia el estado de una cotización existente.</p>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Endpoint</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>PUT /api/sync-client/batch/quotes/{id}/status</code></pre>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Body</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "company_id": 1,
    "status": "approved"
}</code></pre>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1.5">Estados válidos: <code class="text-xs bg-gray-100 px-1">pending</code>, <code class="text-xs bg-gray-100 px-1">approved</code>, <code class="text-xs bg-gray-100 px-1">rejected</code>, <code class="text-xs bg-gray-100 px-1">canceled</code>, <code class="text-xs bg-gray-100 px-1">completed</code></p>
                                </div>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Response</h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "quote_id": 1,
    "status": "approved"
}</code></pre>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="text-xs font-medium text-gray-500 mb-1">Response <span class="text-red-500">404 Not Found</span></h5>
                                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                        <pre class="text-xs text-red-300"><code>{
    "success": false,
    "error": "Quote not found"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- HISTORY --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-history" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('history')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/history</code>
                </div>
                <svg :class="isOpen('history') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('history')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">
                        <p class="text-sm text-gray-600">Obtiene el historial de sincronizaciones realizadas para una empresa.</p>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Query Params</h4>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                        <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600">
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                        <td class="py-2">ID de la empresa</td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">entity_type</code></td>
                                        <td class="py-2">Filtrar por entidad: products, customers, categories, sellers, quotes</td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">from_date</code></td>
                                        <td class="py-2">Fecha mínima (Y-m-d)</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">status</code></td>
                                        <td class="py-2">Filtrar por estado: completed, partial, failed</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "company_id": 1,
                "user_id": 1,
                "entity_type": "products",
                "entity_type_name": "Productos",
                "records_processed": 100,
                "records_created": 80,
                "records_updated": 20,
                "records_failed": 0,
                "status": "completed",
                "status_name": "Completado",
                "error_details": [],
                "started_at": "2024-01-15T10:00:00.000000Z",
                "completed_at": "2024-01-15T10:00:05.000000Z",
                "duration_seconds": 5,
                "duration_formatted": "5s",
                "user": { "id": 1, "name": "Admin", "email": "admin@example.com" },
                "company": { "id": 1, "name": "Nombre Empresa" }
            }
        ],
        "total": 50,
        "per_page": 50,
        "last_page": 1
    }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- LAST SYNC --}}
        {{-- ========================================================================= --}}
        <div id="endpoint-lastsync" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="toggle('lastsync')"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">GET</span>
                    <code class="text-sm text-gray-700 font-mono truncate">/api/sync-client/batch/last-sync</code>
                </div>
                <svg :class="isOpen('lastsync') ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="isOpen('lastsync')" x-collapse>
                <div class="px-5 pb-5 border-t border-gray-100">
                    <div class="pt-4 space-y-5">
                        <p class="text-sm text-gray-600">Obtiene la fecha y estado de la última sincronización de cada entidad (productos, clientes, categorías, vendedores).</p>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Query Params</h4>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-2 pr-4 font-medium text-gray-600 w-48">Parámetro</th>
                                        <th class="text-left py-2 font-medium text-gray-600">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600">
                                    <tr>
                                        <td class="py-2 pr-4"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">company_id</code> <span class="text-red-500">*</span></td>
                                        <td class="py-2">ID de la empresa</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-gray-300"><code>{
    "success": true,
    "company_id": 1,
    "last_syncs": {
        "products": {
            "id": 10,
            "entity_type": "products",
            "entity_type_name": "Productos",
            "records_processed": 150,
            "records_created": 100,
            "records_updated": 50,
            "records_failed": 0,
            "status": "completed",
            "status_name": "Completado",
            "completed_at": "2024-01-15T10:30:00.000000Z",
            "duration_seconds": 12,
            "duration_formatted": "12s"
        },
        "customers": { ... },
        "categories": { ... },
        "sellers": { ... }
    }
}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response <span class="text-red-500">404 Not Found</span></h4>
                            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                                <pre class="text-xs text-red-300"><code>{
    "success": false,
    "message": "Company not found"
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
