@extends('layouts.app')

@section('title', 'Chrystal Mobile - Documentación API')

@section('content')
{{-- Header --}}
<header class="bg-white border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">C</div>
            <span class="font-semibold text-gray-800">Chrystal Mobile</span>
        </div>
        <a href="{{ route('login') }}"
           class="text-sm px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            Login
        </a>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-12">
    {{-- Hero --}}
    <div class="text-center mb-16">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">API de Sincronización</h1>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto">
            Conecta tu sistema con Chrystal a través de nuestra API REST.
            Sincroniza productos, clientes, categorías, vendedores y cotizaciones en tiempo real.
        </p>
    </div>

    {{-- Como funciona --}}
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">¿Cómo funciona?</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg mb-4">1</div>
                <h3 class="font-semibold text-gray-900 mb-2">Obtén tu API Key</h3>
                <p class="text-sm text-gray-500">Cada empresa recibe una API Key única. Úsala en el header <code class="text-indigo-600 text-xs bg-indigo-50 px-1 py-0.5 rounded">Authorization: Bearer</code>.</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg mb-4">2</div>
                <h3 class="font-semibold text-gray-900 mb-2">Envía tus datos</h3>
                <p class="text-sm text-gray-500">Usa los endpoints batch para enviar productos, clientes, categorías y vendedores en lotes de hasta 5,000 registros.</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg mb-4">3</div>
                <h3 class="font-semibold text-gray-900 mb-2">Verifica el estado</h3>
                <p class="text-sm text-gray-500">Revisa el historial de sincronización y el estado de cada lote para confirmar que todo se procesó correctamente.</p>
            </div>
        </div>
    </section>

    {{-- Endpoints --}}
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Endpoints</h2>
        <p class="text-gray-500 mb-6 text-sm">URL Base: <code class="text-indigo-600 bg-indigo-50 px-2 py-1 rounded text-sm">https://chrystal.com.ve/mobiletest/public/api/sync-client</code></p>

        <div class="space-y-4">
            {{-- Ping --}}
            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">GET</span>
                    <code class="text-sm font-mono flex-1">/sync-client/ping</code>
                    <span class="text-xs text-gray-400">Verificar conexión</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Verifica que la API key es válida y retorna los datos de la empresa.</p>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto"><span class="text-gray-400"># Request</span>
curl -X GET https://chrystal.com.ve/mobiletest/public/api/sync-client/ping \
  -H "Authorization: Bearer tu-api-key" \
  -H "Accept: application/json"

<span class="text-gray-400"># Response</span>
{
  "success": true,
  "message": "Conexión exitosa",
  "data": {
    "id": 1,
    "empresa": "Mi Empresa S.A.",
    "rif": "J123456789",
    "email": "empresa@correo.com"
  }
}</pre>
                </div>
            </details>

            {{-- Company Validate --}}
            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded">POST</span>
                    <code class="text-sm font-mono flex-1">/sync-client/company/validate</code>
                    <span class="text-xs text-gray-400">Validar o crear empresa</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Valida si la empresa existe por RIF y email. Si no existe, la crea automáticamente.</p>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto"><span class="text-gray-400"># Request</span>
curl -X POST https://chrystal.com.ve/mobiletest/public/api/sync-client/company/validate \
  -H "Authorization: Bearer tu-api-key" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "rif": "J123456789",
    "email": "empresa@correo.com",
    "name": "Mi Empresa S.A."
  }'</pre>
                </div>
            </details>

            {{-- Batch Products --}}
            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">GET</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/products</code>
                    <span class="text-xs text-gray-400">Obtener productos</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Obtiene productos paginados (50 por página). Soporta búsqueda y filtros.</p>
                    <p class="text-xs text-gray-400 mb-1">Parámetros: <code>company_id</code> (requerido), <code>search</code>, <code>category_id</code>, <code>page</code></p>
                </div>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-bold rounded">POST</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/products</code>
                    <span class="text-xs text-gray-400">Sincronizar productos</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Crea o actualiza productos (UPSERT por código). Máximo 5,000 por lote.</p>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto"><span class="text-gray-400"># Request</span>
curl -X POST https://chrystal.com.ve/mobiletest/public/api/sync-client/batch/products \
  -H "Authorization: Bearer tu-api-key" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "products": [
      {
        "code": "PROD001",
        "name": "Laptop HP",
        "price": 800.00,
        "cost": 600.00,
        "coin": "USD",
        "stock": 50,
        "status": "active"
      }
    ]
  }'</pre>
                </div>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">DELETE</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/products</code>
                    <span class="text-xs text-gray-400">Eliminar productos</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Elimina productos por códigos.</p>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto"><span class="text-gray-400"># Request</span>
curl -X DELETE https://chrystal.com.ve/mobiletest/public/api/sync-client/batch/products \
  -H "Authorization: Bearer tu-api-key" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_id": 1,
    "codes": ["PROD001", "PROD002"]
  }'</pre>
                </div>
            </details>

            {{-- Batch Customers --}}
            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">GET</span>
                    <span class="text-sm font-mono flex-1">/sync-client/batch/customers</span>
                    <span class="text-xs text-gray-400">Obtener clientes</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Parámetros: <code>company_id</code> (requerido), <code>search</code>, <code>page</code></p>
                </div>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-bold rounded">POST</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/customers</code>
                    <span class="text-xs text-gray-400">Sincronizar clientes</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Campos requeridos: <code>name</code>, <code>document_number</code>. UPSERT por <code>codigo</code>.</p>
                </div>
            </details>

            {{-- Batch Quotes --}}
            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">GET</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/quotes</code>
                    <span class="text-xs text-gray-400">Obtener cotizaciones</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Parámetros: <code>company_id</code> (requerido), <code>status</code> (draft, approved), <code>from_date</code></p>
                </div>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-bold rounded">POST</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/quotes</code>
                    <span class="text-xs text-gray-400">Crear cotización</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Crea una cotización con sus items. Requiere <code>company_id</code>, <code>customer_id</code>, <code>items</code>.</p>
                </div>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded">PUT</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/quotes/{id}/status</code>
                    <span class="text-xs text-gray-400">Actualizar estado</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Actualiza el estado de una cotización: <code>pending</code>, <code>approved</code>, <code>rejected</code>, <code>canceled</code>.</p>
                </div>
            </details>

            {{-- Otros endpoints --}}
            <details class="bg-white border border-gray-200 rounded-xl group">
                <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">GET</span>
                    <code class="text-sm font-mono flex-1">/sync-client/batch/history</code>
                    <span class="text-xs text-gray-400">Historial de sincronización</span>
                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-5 pb-4 border-t border-gray-100 pt-3">
                    <p class="text-sm text-gray-500 mb-3">Parámetros: <code>company_id</code>, <code>entity_type</code> (products, customers, categories, sellers), <code>from_date</code></p>
                </div>
            </details>
        </div>
    </section>

    {{-- Rate Limits --}}
    <!-- <section class="mb-16 bg-indigo-50 border border-indigo-100 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-indigo-900 mb-3">Límites de uso</h2>
        <ul class="text-sm text-indigo-700 space-y-2">
            <li>• Máximo <strong>100 peticiones por minuto</strong> por empresa.</li>
            <li>• Máximo <strong>5,000 registros por lote</strong> en operaciones batch.</li>
            <li>• Si excedes el límite, recibirás un <code class="bg-indigo-200 px-1 rounded">HTTP 429</code> y debes esperar antes de reintentar.</li>
        </ul>
    </section>-->

    {{-- Footer --}}
    <footer class="border-t border-gray-100 pt-8 text-center text-sm text-gray-400">
        <p>Chrystal Mobile &mdash; Sistema de sincronización de datos</p>
    </footer>
</main>
@endsection
