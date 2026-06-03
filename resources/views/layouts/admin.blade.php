<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Chrystal Mobile')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
<div class="min-h-screen md:flex md:flex-row">
    {{-- Sidebar desktop --}}
    <aside class="hidden md:block md:w-64 md:shrink-0 md:min-h-screen bg-white border-r border-gray-200 md:flex md:flex-col">
        <div class="p-5 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="font-semibold text-gray-900">Chrystal Mobile</span>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="{{ route('admin.accesos') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.accesos*') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                </svg>
                Empresas
            </a>
            @if(auth()->check() && auth()->user()->role->value === 'manager')
             <a href="{{ route('admin.usuarios') }}"
 class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.usuarios*') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0
   00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
      </svg>
      Usuarios
  </a>

            <a href="{{ route('admin.docs') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.docs') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Docs
            </a>
            @endif
        </nav>
        <div class="p-4 border-t border-gray-100 shrink-0">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-sm font-medium text-gray-600 shrink-0">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->role->value === 'admin' ? 'Admin' : 'Manager' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-gray-600 shrink-0" title="Cerrar sesión">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Contenido del main --}}
    <div class="flex-1 flex flex-col min-h-screen">
        {{-- Header móvil --}}
        <div class="md:hidden sticky top-0 z-30 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 bg-gray-900 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="font-semibold text-sm text-gray-900">Chrystal Mobile</span>
            </div>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="p-1.5 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="fixed right-4 top-3 w-56 bg-white border border-gray-200 rounded-xl shadow-sm py-2">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ Auth::user()->role->value === 'admin' ? 'Admin' : 'Manager' }}</p>
                    </div>
                    <a href="{{ route('admin.accesos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Empresas</a>
                    @if(auth()->check() && auth()->user()->role->value === 'manager')
                        <a href="{{ route('admin.usuarios') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Usuarios</a>
                        <a href="{{ route('admin.docs') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Docs</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 pt-1">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Notificaciones flash --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mx-4 mt-4 md:mx-6 md:mt-6 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
                <button @click="show = false" class="ml-auto text-emerald-500 hover:text-emerald-700">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mx-4 mt-4 md:mx-6 md:mt-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
                <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        {{-- Contenido --}}
        <main class="flex-1">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>