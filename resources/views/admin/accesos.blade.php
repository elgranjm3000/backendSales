@extends('layouts.admin')

@section('title', 'Empresas - Chrystal Mobile')

@section('content')
<div x-data="{
    modalOpen: {{ isset($accesoEdit) || $errors->any() ? 'true' : 'false' }},
    isEdit: {{ isset($accesoEdit) ? 'true' : 'false' }},
    openCreate() {
        this.isEdit = false;
        this.$nextTick(() => {
            const fields = ['form-nombre', 'form-id_fiscal', 'form-email', 'form-telefono', 'form-ciudad', 'form-estado', 'form-direccion'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                el.value = '';
                el.classList.remove('border-red-500');
                el.classList.add('border-gray-300');
            });
            // Ocultar mensajes de error
            document.querySelectorAll('.text-red-600').forEach(el => el.remove());
        });
    },
    openEdit() { this.isEdit = true; }
}" class="p-4 md:p-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Empresas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestión de API keys y estados de sincronización</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400">{{ $accesos->total() }} registros</span>
            <button @click="modalOpen = true; openCreate()" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Empresa
            </button>
        </div>
    </div>

    {{-- Filtros y búsqueda --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
        <form method="GET" action="{{ route('admin.accesos') }}" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                       placeholder="Buscar por nombre, RIF, email...">
            </div>
            <select name="filter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">Todos los estados</option>
                <option value="active" @selected(request('filter') === 'active')>Activos</option>
                <option value="blocked" @selected(request('filter') === 'blocked')">Desactivados</option>
                <option value="no_key" @selected(request('filter') === 'no_key')">Sin API Key</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Filtrar
            </button>
            @if(request()->has('search') || request()->has('filter'))
                <a href="{{ route('admin.accesos') }}" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-center">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- Vista móvil: tarjetas --}}
    <div class="md:hidden space-y-3">
        @forelse ($accesos as $acceso)
            <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
                <div>
                    <div class="font-medium text-gray-900 truncate">{{ $acceso->nombre ?: 'Sin nombre' }}</div>
                    @if($acceso->ciudad)
                        <div class="text-xs text-gray-400">{{ $acceso->ciudad }}</div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-xs text-gray-400 block">RIF</span>
                        <span class="text-gray-700">{{ $acceso->codigo }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Email</span>
                        <span class="text-gray-700 text-xs truncate block">{{ $acceso->correo_electronico ?? '-' }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-xs text-gray-400 block">API Key</span>
                    @if($acceso->api_key)
                        <div x-data="{ show: false, copied: false, copyKey(key) { if (navigator.clipboard) { navigator.clipboard.writeText(key).then(() => this.copied = true).catch(() => { const el = document.createElement('textarea'); el.value = key; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el); this.copied = true; }); } else { const el = document.createElement('textarea'); el.value = key; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el); this.copied = true; } setTimeout(() => this.copied = false, 2000); } }" class="flex items-center gap-1 mt-0.5">
                            <code x-text="show ? '{{ $acceso->api_key }}' : '{{ substr($acceso->api_key, 0, 12) }}...'" class="text-xs text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded break-all"></code>
                            <button @click="show = !show" class="p-1 text-gray-400 hover:text-gray-600 shrink-0">
                                <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            <button @click="copyKey('{{ $acceso->api_key }}')" class="p-1 text-gray-400 hover:text-gray-600 shrink-0">
                                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span x-show="copied" class="text-green-600 text-xs" x-cloak>Copiado</span>
                            </button>
                        </div>
                    @else
                        <span class="text-xs text-gray-400">Sin API key</span>
                    @endif
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div class="flex items-center gap-2">
                        @if($acceso->isBlocked())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                Desactivado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-medium rounded-full">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                Activo
                            </span>
                        @endif
                        @if($acceso->blocked_at)
                            <span class="text-xs text-gray-400">{{ $acceso->blocked_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.accesos.toggle-block', $acceso->id) }}"
                          onsubmit="return confirm('{{ $acceso->isBlocked() ? '¿Activar' : '¿Desactivar' }} a {{ $acceso->nombre }}?')">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $acceso->isBlocked() ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                            {{ $acceso->isBlocked() ? 'Activar' : 'Desactivar' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p>No se encontraron empresas</p>
            </div>
        @endforelse
    </div>

    {{-- Vista desktop: tabla --}}
    <div class="hidden md:block bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">Empresa</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">RIF</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">Email</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">Vendedores</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">API Key</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">Estado</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">Desactivado</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600 text-xs uppercase tracking-wider">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($accesos as $acceso)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $acceso->nombre ?: 'Sin nombre' }}</div>
                                @if($acceso->ciudad)
                                    <div class="text-xs text-gray-400">{{ $acceso->ciudad }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $acceso->codigo }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $acceso->correo_electronico ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($acceso->company && $acceso->company->sellers->count() > 0)
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click="open = !open" class="flex items-center gap-1 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded-full text-xs font-medium text-gray-700">
                                            @if($acceso->company->sellers->count() <= 5)
                                                @foreach($acceso->company->sellers->take(5) as $seller)
                                                    <span class="inline-block">{{ $seller->code ?? substr($seller->name, 0, 10) }}</span>
                                                @endforeach
                                            @else
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10a2 2 0 002 2v3a2 2 0 002-2H7a2 2 0 00-2 2v-2a2 2 0 012-2h.01M12 7a2 2 0 012 2v0a2 2 0 00-2 2v-3a2 2 0 002 2h.01"/></svg>
                                                    <span>{{ $acceso->company->sellers->count() }}</span>
                                                </span>
                                            @endif
                                        </button>
                                        <div x-show="open" @click.outside="open = false" x-cloak class="absolute left-0 top-full mt-1 z-20 bg-white border border-gray-200 rounded-xl shadow-xl py-2 px-3 min-w-[280px]">
                                            <div class="text-xs font-medium text-gray-500 mb-2">
                                                {{ $acceso->company->sellers->count() }} Vendedor{{ $acceso->company->sellers->count() == 1 ? '' : 'es' }}
                                            </div>
                                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                                @foreach($acceso->company->sellers as $seller)
                                                    <div x-data="{ mobilecheck: {{ !!$seller->mobilecheck }} }" class="flex items-center justify-between gap-2 p-2 hover:bg-gray-50 rounded-lg">
                                                        <div class="flex items-center gap-2">
                                                            <label class="relative inline-flex items-center cursor-pointer">
                                                                <input type="checkbox"
                                                                       class="sr-only peer"
                                                                       x-model="mobilecheck"
                                                                       @change="window.toggleMobilecheck('{{ $seller->id }}')">
                                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600 peer-checked:border-indigo-600"></div>
                                                            </label>
                                                            <span class="text-xs text-gray-700">{{ $seller->code }}</span>
                                                        </div>
                                                        <span class="text-xs" x-text="mobilecheck === 1 ? 'Habilitado' : 'Deshabilitado'" :class="mobilecheck === 1 ? 'text-green-600' : 'text-gray-400'"></span>
                                                    </div>

                                                @endforeach
                                            </div>
                                            </div>
                           
                                      
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Sin Vendedores</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-xs text-gray-400">
                                        @if($acceso->api_key)
                        <div x-data="{ show: false, copied: false, copyKey(key) { if (navigator.clipboard) { navigator.clipboard.writeText(key).then(() => this.copied = true).catch(() => { const el = document.createElement('textarea'); el.value = key; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el); this.copied = true; }); } else { const el = document.createElement('textarea'); el.value = key; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el); this.copied = true; } setTimeout(() => this.copied = false, 2000); } }" class="flex items-center gap-1 mt-0.5">
                            <code x-text="show ? '{{ $acceso->api_key }}' : '{{ substr($acceso->api_key, 0, 12) }}...'" class="text-xs text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded break-all"></code>
                            <button @click="show = !show" class="p-1 text-gray-400 hover:text-gray-600 shrink-0">
                                <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            <button @click="copyKey('{{ $acceso->api_key }}')" class="p-1 text-gray-400 hover:text-gray-600 shrink-0">
                                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span x-show="copied" class="text-green-600 text-xs" x-cloak>Copiado</span>
                            </button>
                        </div>
                    @else
                        <span class="text-xs text-gray-400">Sin API key</span>
                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($acceso->isBlocked())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">
                                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                        Desactivado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-medium rounded-full">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                        Activo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $acceso->blocked_at ? $acceso->blocked_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.accesos.edit', $acceso->id) }}" @click="openEdit({{ $acceso->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 hover:bg-gray-100 transition-colors mr-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414 2 2 0 112.828 0 4-4L4 16l-4-4m0 0L4 12l4-4m0 0l4 4" />
                                    </svg>
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.accesos.toggle-block', $acceso->id) }}" class="inline"
                                      onsubmit="return confirm('{{ $acceso->isBlocked() ? '¿Activar' : '¿Desactivar' }} a {{ $acceso->nombre }}?')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $acceso->isBlocked() ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                                        {{ $acceso->isBlocked() ? 'Activar' : 'Desactivar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                No se encontraron empresas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    <div class="mt-4">
        {{ $accesos->links() }}
    </div>

    {{-- Modal Crear Empresa --}}
    <div @keydown.escape.window="modalOpen = false" x-cloak>
        {{-- Backdrop --}}
        <div x-show="modalOpen" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 z-40" @click="modalOpen = false"></div>

        {{-- Modal --}}
        <div x-show="modalOpen" x-transition:enter="transition-all duration-300" x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition-all duration-200"
             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">{{ isset($accesoEdit) || request()->isMethod('PUT') ? 'Editar Empresa' : 'Nueva Empresa' }}</h2>
                    <button @click="modalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Formulario --}}
                <form method="POST" action="{{ isset($accesoEdit) ? route('admin.accesos.update', $accesoEdit->id) : route('admin.accesos.store') }}" class="p-6 space-y-4">
                    @csrf
                    @if(isset($accesoEdit))
                        <input type="hidden" name="_method" value="PUT">
                    @elseif(old('_method') === 'PUT')
                        <input type="hidden" name="_method" value="PUT">
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Nombre --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" id="form-nombre" name="nombre" value="{{ old('nombre', isset($accesoEdit) ? $accesoEdit->nombre : '') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none {{ $errors->has('nombre') ? 'border-red-500' : '' }}"
                                   placeholder="Ej: Mi Empresa S.A.">
                            @error('nombre')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- RIF/ID Fiscal --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RIF / ID Fiscal *</label>
                            <input type="text" id="form-id_fiscal" name="id_fiscal" value="{{ old('id_fiscal', isset($accesoEdit) ? $accesoEdit->id_fiscal : '') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none {{ $errors->has('id_fiscal') ? 'border-red-500' : '' }}"
                                   placeholder="Ej: J-12345678-9">
                            @error('id_fiscal')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                            <input type="email" id="form-email" name="correo_electronico" value="{{ old('correo_electronico', isset($accesoEdit) ? $accesoEdit->correo_electronico : '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none {{ $errors->has('correo_electronico') ? 'border-red-500' : '' }}"
                                   placeholder="correo@empresa.com">
                            @error('correo_electronico')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Teléfono --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="text" id="form-telefono" name="telefono" value="{{ old('telefono', isset($accesoEdit) ? $accesoEdit->telefono : '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none {{ $errors->has('telefono') ? 'border-red-500' : '' }}"
                                   placeholder="Ej: 0251-1234567">
                            @error('telefono')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Ciudad --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                            <input type="text" id="form-ciudad" name="ciudad" value="{{ old('ciudad', isset($accesoEdit) ? $accesoEdit->ciudad : '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none {{ $errors->has('ciudad') ? 'border-red-500' : '' }}"
                                   placeholder="Ej: Valencia">
                            @error('ciudad')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Estado --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <input type="text" id="form-estado" name="estado" value="{{ old('estado', isset($accesoEdit) ? $accesoEdit->estado : '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none {{ $errors->has('estado') ? 'border-red-500' : '' }}"
                                   placeholder="Ej: Carabobo">
                            @error('estado')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Dirección --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <textarea id="form-direccion" name="direccion" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none {{ $errors->has('direccion') ? 'border-red-500' : '' }}"
                                  placeholder="Dirección física de la empresa">{{ old('direccion', isset($accesoEdit) ? $accesoEdit->direccion : '') }}</textarea>
                        @error('direccion')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Info --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-blue-800">{{ isset($accesoEdit) || old('_method') === 'PUT' ? 'El código se actualizará automáticamente desde el RIF. La API Key existente se mantendrá.' : 'El código se generará automáticamente desde el RIF. Se creará una API Key única para esta empresa.' }}</p>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                            {{ isset($accesoEdit) || old('_method') === 'PUT' ? 'Actualizar Empresa' : 'Crear Empresa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
window.toggleMobilecheck = function(sellerId) {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaTag ? metaTag.getAttribute('content') : document.querySelector('input[name="_token"]')?.value;
    
    if (!csrfToken) {
        console.error('Token CSRF no encontrado');
        return;
    }
    
    const route = '/mobiletest/public/admin/sellers/' + sellerId + '/toggle-mobilecheck';
    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ _method: 'POST' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
};
</script>
@endsection
