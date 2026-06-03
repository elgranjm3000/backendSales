@extends('layouts.admin')

@section('title', 'Empresas - Chrystal Mobile')

@section('content')
<div x-data="accesosData()" x-init="init()" class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Empresas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestiona empresas, API keys y acceso de vendedores</p>
        </div>
        <button @click="openCreate()" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva empresa
        </button>
    </div>

    {{-- Filtros --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('admin.accesos') }}" id="filterForm">
            <div class="flex flex-col lg:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="flex-1 lg:flex-none lg:w-64 px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                       placeholder="Buscar...">
                <div class="flex flex-col sm:flex-row gap-3">
                    <select name="filter" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-600 outline-none transition-all focus:border-gray-900 min-w-[140px]">
                        <option value="">Estado empresa</option>
                        <option value="active" {{ request('filter') === 'active' ? 'selected' : '' }}>Activos</option>
                        <option value="blocked" {{ request('filter') === 'blocked' ? 'selected' : '' }}>Bloqueados</option>
                        <option value="no_key" {{ request('filter') === 'no_key' ? 'selected' : '' }}>Sin API key</option>
                    </select>
                    <select name="sync" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-600 outline-none transition-all focus:border-gray-900 min-w-[140px]">
                        <option value="">Sincronización</option>
                        <option value="synced" {{ request('sync') === 'synced' ? 'selected' : '' }}>Sincronizados</option>
                        <option value="unsynced" {{ request('sync') === 'unsynced' ? 'selected' : '' }}>No sincronizados</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Empresa</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">RIF</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Sinc.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">API Key</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Vendedores</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Horas Offline</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($accesos as $acceso)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $acceso->nombre ?: 'Sin nombre' }}</div>
                                @if($acceso->correo_electronico)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $acceso->correo_electronico }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $acceso->codigo }}</td>
                            <td class="px-4 py-3">
                                @if($acceso->correo_electronico && in_array($acceso->correo_electronico, $registeredEmails ?? []))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700" title="Email existe en tabla companies">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Sincronizado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700" title="Email no existe en tabla companies - requiere sincronización">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        No sincronizado
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($acceso->api_key)
                                    <div x-data="{ show: false, copied: false }" class="flex items-center gap-2">
                                        <code x-text="show ? '{{ $acceso->api_key }}' : '{{ substr($acceso->api_key, 0, 8) }}...'" class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded font-mono cursor-pointer hover:bg-gray-200 transition-colors" @click="show = !show"></code>
                                        <button @click="navigator.clipboard.writeText('{{ $acceso->api_key }}'); copied = true; setTimeout(() => copied = false, 2000)" class="text-gray-400 hover:text-gray-600 transition-colors" title="Copiar API key">
                                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            <svg x-show="copied" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Sin key</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($acceso->company && $acceso->company->sellers->isNotEmpty())
                                    <button @click="showVendorsModal = true; currentSellers = {{ $acceso->company->sellers->map(fn($s) => ['id' => $s->id, 'description' => $s->description, 'email' => $s->user->email ?? $s->code, 'mobilecheck' => !!$s->mobilecheck])->toJson() }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $acceso->company->sellers->count() }}
                                    </button>
                                @else
                                    <span class="text-sm text-gray-400">0 vendedores</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($acceso->company)
                                    <div class="flex items-center gap-2">
                                        <input type="number"
                                               value="{{ $acceso->company->offline_token_hours ?? 24 }}"
                                               min="1"
                                               max="720"
                                               class="w-20 px-2 py-1 border border-gray-200 rounded text-sm text-center"
                                               onchange="updateOfflineHours({{ $acceso->company->id }}, this.value)">
                                        <span class="text-xs text-gray-500">hs</span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($acceso->blocked_at)
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-sm text-red-600">Bloqueado</span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $acceso->blocked_at?->format('d/m/Y') }}</span>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span class="text-sm text-emerald-600">Activo</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEdit({{ $acceso->id }})" :disabled="loading && editId === {{ $acceso->id }}" class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 p-2 rounded-lg transition-all disabled:opacity-50" title="Editar empresa">
                                        <svg x-show="!loading || editId !== {{ $acceso->id }}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <svg x-show="loading && editId === {{ $acceso->id }}" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('admin.accesos.toggle-block', $acceso->id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                                        <input type="hidden" name="sync" value="{{ request('sync') }}">
                                        <button type="submit" class="p-2 rounded-lg transition-all {{ $acceso->blocked_at ? 'text-emerald-600 hover:bg-emerald-50' : 'text-red-600 hover:bg-red-50' }}" title="{{ $acceso->blocked_at ? 'Activar empresa y restaurar acceso' : 'Bloquear empresa y suspender acceso' }}">
                                            @if($acceso->blocked_at)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                                    <circle cx="12" cy="16" r="1"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                                    </svg>
                                    <p class="text-gray-500">No hay empresas registradas</p>
                                    <button @click="openCreate()" class="text-sm text-gray-900 hover:text-gray-600 transition-colors">Crear primera empresa</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    <div class="mt-6">
        {{ $accesos->links() }}
    </div>

    {{-- Modal Crear/Editar --}}
    <div @keydown.escape.window="modalOpen = false" x-cloak>
        <div x-show="modalOpen" x-transition.opacity.duration.200ms class="fixed inset-0 bg-gray-900/40 z-40" @click="modalOpen = false"></div>
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-gray-200 rounded-lg w-full max-w-md shadow-sm" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900" x-text="isEdit ? 'Editar empresa' : 'Nueva empresa'"></h2>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6">
                    @csrf
                    <input type="hidden" name="_method" x-model="isEdit ? 'PUT' : 'POST'">

                    <div class="space-y-4">
                        <div>
                            <label for="form-nombre" class="block text-sm font-medium text-gray-700 mb-1.5">Nombre *</label>
                            <input type="text" id="form-nombre" name="nombre" required
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                   placeholder="Nombre de la empresa">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="form-id_fiscal" class="block text-sm font-medium text-gray-700 mb-1.5">RIF *</label>
                                <input type="text" id="form-id_fiscal" name="id_fiscal" required
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       placeholder="J123456789">
                            </div>
                            <div>
                                <label for="form-email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                <input type="email" id="form-email" name="correo_electronico"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       placeholder="email@empresa.com">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="form-ciudad" class="block text-sm font-medium text-gray-700 mb-1.5">Ciudad</label>
                                <input type="text" id="form-ciudad" name="ciudad"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       placeholder="Caracas">
                            </div>
                            <div>
                                <label for="form-estado" class="block text-sm font-medium text-gray-700 mb-1.5">Estado</label>
                                <input type="text" id="form-estado" name="estado"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       placeholder="Distrito Capital">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="form-telefono" class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
                                <input type="text" id="form-telefono" name="telefono"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       placeholder="0414-1234567">
                            </div>
                            <div>
                                <label for="form-direccion" class="block text-sm font-medium text-gray-700 mb-1.5">Dirección</label>
                                <textarea id="form-direccion" name="direccion" rows="1"
                                          class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white resize-none"
                                          placeholder="Dirección física"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 mt-6">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm text-gray-600 font-medium hover:text-gray-900 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm text-white font-medium bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                            <span x-text="isEdit ? 'Actualizar' : 'Crear'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Vendedores --}}
    <div x-cloak>
        <div x-show="showVendorsModal" x-transition.opacity.duration.200ms class="fixed inset-0 bg-gray-900/40 z-50" @click="showVendorsModal = false"></div>
        <div x-show="showVendorsModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-gray-200 rounded-lg w-full max-w-md shadow-sm" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Vendedores</h2>
                    <button @click="showVendorsModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-2 max-h-96 overflow-y-auto">
                    <template x-if="currentSellers.length > 0">
                        <template x-for="seller in currentSellers" :key="seller.id">
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               class="sr-only peer"
                                               x-model="seller.mobilecheck"
                                               @change="window.toggleMobilecheck(seller.id)">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-gray-900 peer-checked:border-gray-900"></div>
                                    </label>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="seller.description"></div>
                                        <div class="text-xs text-gray-500" x-text="seller.email"></div>
                                    </div>
                                </div>
                                <span class="text-xs font-medium" x-text="seller.mobilecheck ? 'Habilitado' : 'Deshabilitado'" :class="seller.mobilecheck ? 'text-emerald-600' : 'text-gray-400'"></span>
                            </div>
                        </template>
                    </template>
                    <template x-if="currentSellers.length === 0">
                        <div class="text-center py-8">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-sm text-gray-500">No hay vendedores asignados</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.APP_URL = "{{ url('/') }}";

function accesosData() {
    return {
        modalOpen: false,
        isEdit: false,
        editId: null,
        editData: {},
        loading: false,
        showVendorsModal: false,
        currentSellers: [],
        async openEdit(id) {
            this.loading = true;
            this.editId = id;
            try {
                const response = await fetch(window.APP_URL + '/admin/accesos/' + id + '/edit-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                const result = await response.json();
                if (result.success) {
                    this.editData = result.data;
                    this.isEdit = true;
                    this.modalOpen = true;
                    this.$nextTick(() => {
                        document.getElementById('form-nombre').value = result.data.nombre || '';
                        document.getElementById('form-id_fiscal').value = result.data.id_fiscal || '';
                        document.getElementById('form-email').value = result.data.correo_electronico || '';
                        document.getElementById('form-ciudad').value = result.data.ciudad || '';
                        document.getElementById('form-estado').value = result.data.estado || '';
                        document.getElementById('form-telefono').value = result.data.telefono || '';
                        document.getElementById('form-direccion').value = result.data.direccion || '';
                    });
                }
            } catch (e) {
                console.error('Error al cargar datos:', e);
            } finally {
                this.loading = false;
            }
        },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.editData = {};
            this.$nextTick(() => {
                const fields = ['form-nombre', 'form-id_fiscal', 'form-email', 'form-telefono', 'form-ciudad', 'form-estado', 'form-direccion'];
                fields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.value = '';
                        el.classList.remove('border-red-500');
                        el.classList.add('border-gray-200');
                    }
                });
                document.querySelectorAll('.text-red-600').forEach(el => el.remove());
            });
            this.modalOpen = true;
        },
        async submitForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const url = this.isEdit ? window.APP_URL + '/admin/accesos/' + this.editId : window.APP_URL + '/admin/accesos';

            // Mapeo de nombres de Laravel a IDs de HTML
            const fieldMap = {
                'nombre': 'form-nombre',
                'id_fiscal': 'form-id_fiscal',
                'correo_electronico': 'form-email',
                'ciudad': 'form-ciudad',
                'estado': 'form-estado',
                'telefono': 'form-telefono',
                'direccion': 'form-direccion'
            };

            // Limpiar errores anteriores
            document.querySelectorAll('.text-red-600').forEach(el => el.remove());
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500');
                el.classList.add('border-gray-200');
            });

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    this.modalOpen = false;
                    window.location.reload();
                } else if (result.errors) {
                    // Mostrar errores inline
                    for (const [field, messages] of Object.entries(result.errors)) {
                        const inputId = fieldMap[field] || 'form-' + field;
                        const input = document.getElementById(inputId);
                        if (input) {
                            input.classList.remove('border-gray-200');
                            input.classList.add('border-red-500');
                            // Agregar mensaje de error
                            const errorDiv = document.createElement('p');
                            errorDiv.className = 'mt-1.5 text-sm text-red-600';
                            errorDiv.textContent = messages[0];
                            input.parentElement.appendChild(errorDiv);
                        }
                    }
                } else {
                    alert(result.message || 'Error al procesar la solicitud');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Error al procesar la solicitud');
            }
        },
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                this.openEdit(editId);
            }
        }
    };
}

window.toggleMobilecheck = function(sellerId) {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaTag ? metaTag.getAttribute('content') : document.querySelector('input[name="_token"]')?.value;

    fetch(window.APP_URL + '/admin/sellers/' + sellerId + '/toggle-mobilecheck', {
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

window.updateOfflineHours = function(companyId, hours) {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaTag ? metaTag.getAttribute('content') : document.querySelector('input[name="_token"]')?.value;

    fetch(window.APP_URL + '/admin/companies/' + companyId + '/offline-hours', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ offline_token_hours: parseInt(hours) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar notificación simple
            const notification = document.createElement('div');
            notification.className = 'fixed bottom-4 right-4 bg-emerald-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            notification.textContent = data.message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        } else {
            alert(data.message || 'Error al actualizar');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar las horas offline');
    });
};
</script>
@endsection