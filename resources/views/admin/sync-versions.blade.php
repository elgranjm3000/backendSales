@extends('layouts.admin')

@section('title', 'Versiones de App - Chrystal Mobile')

@section('content')
<div x-data="syncVersionsData()" x-init="init()" class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Versiones de App</h1>
            <p class="text-sm text-gray-500 mt-0.5">Controla qué versiones pueden sincronizar con el servidor</p>
        </div>
        <button @click="openCreate()" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva versión
        </button>
    </div>

    {{-- Tabla --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Tipo</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Versión</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Notas</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Creado</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($versions as $version)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium
                                    @if($version->typeapp === 'mobile') bg-blue-50 text-blue-700
                                    @elseif($version->typeapp === 'sincronizador') bg-amber-50 text-amber-700
                                    @elseif($version->typeapp === 'chrystal') bg-purple-50 text-purple-700
                                    @else bg-gray-50 text-gray-700
                                    @endif">
                                    {{ ucfirst($version->typeapp) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <code class="text-sm font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded">{{ $version->version }}</code>
                            </td>
                            <td class="px-4 py-3">
                                @if($version->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Activa
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Inactiva
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate" title="{{ $version->notes }}">
                                {{ $version->notes ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $version->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEdit({{ $version->id }})" class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 p-2 rounded-lg transition-all" title="Editar versión">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="confirmDelete({{ $version->id }}, '{{ $version->version }}')" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all" title="Eliminar versión">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if($versions->isEmpty())
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10"/>
                                    </svg>
                                    <p class="text-gray-500">No hay versiones registradas</p>
                                    <button @click="openCreate()" class="text-sm text-gray-900 hover:text-gray-600 transition-colors">Crear primera versión</button>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    <div @keydown.escape.window="modalOpen = false" x-cloak>
        <div x-show="modalOpen" x-transition.opacity.duration.200ms class="fixed inset-0 bg-gray-900/40 z-40" @click="modalOpen = false"></div>
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-gray-200 rounded-lg w-full max-w-md shadow-sm" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900" x-text="isEdit ? 'Editar versión' : 'Nueva versión'"></h2>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-4">
                        <div>
                            <label for="form-typeapp" class="block text-sm font-medium text-gray-700 mb-1.5">Tipo de App *</label>
                            <select id="form-typeapp" name="typeapp" required
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-600 outline-none transition-all focus:border-gray-900">
                                <option value="mobile">Mobile</option>
                                <option value="sincronizador">Sincronizador</option>
                                <option value="chrystal">Chrystal</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Tipo de cliente que se conecta al servidor</p>
                        </div>

                        <div>
                            <label for="form-version" class="block text-sm font-medium text-gray-700 mb-1.5">Versión *</label>
                            <input type="text" id="form-version" name="version" required
                                   :disabled="isEdit"
                                   pattern="^\d+\.\d+\.\d+(-[a-z0-9]+)?$"
                                   title="Formato: 1.0.0 o 1.0.0-beta"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                   placeholder="Ej: 1.0.0">
                            <p class="text-xs text-gray-400 mt-1">Formato: mayor.minor.patch (ej: 1.0.0)</p>
                        </div>

                        <div>
                            <label for="form-status" class="block text-sm font-medium text-gray-700 mb-1.5">Estado *</label>
                            <select id="form-status" name="status" required
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-600 outline-none transition-all focus:border-gray-900">
                                <option value="active">Activa</option>
                                <option value="inactive">Inactiva</option>
                            </select>
                        </div>

                        <div>
                            <label for="form-notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notas</label>
                            <textarea id="form-notes" name="notes" rows="2"
                                      class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white resize-none"
                                      placeholder="Notas de versión o razón de bloqueo..."></textarea>
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

    {{-- Modal Confirmar Eliminación --}}
    <div x-cloak>
        <div x-show="showDeleteModal" x-transition.opacity.duration.200ms class="fixed inset-0 bg-gray-900/40 z-50" @click="showDeleteModal = false"></div>
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-gray-200 rounded-lg w-full max-w-md shadow-sm" @click.stop>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Eliminar versión</h3>
                            <p class="text-sm text-gray-500 mt-1">Esta acción eliminará la versión del sistema</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 mb-6">¿Estás seguro de que deseas eliminar <strong x-text="deleteItemVersion"></strong>? Esta acción no se puede deshacer.</p>

                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm text-gray-600 font-medium hover:text-gray-900 transition-colors">
                            Cancelar
                        </button>
                        <button @click="executeDelete()" class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.APP_URL = "{{ url('/') }}";

function checkSessionExpired(response) {
    if (response.status === 419 || (response.headers.get('content-type')?.includes('application/json'))) {
        response.clone().json().then(data => {
            if (data.message?.includes('CSRF') || data.exception?.includes('HttpException')) {
                window.location.href = window.APP_URL + '/login?session=expired';
            }
        }).catch(() => {
            if (response.status === 419) {
                window.location.href = window.APP_URL + '/login?session=expired';
            }
        });
    }
    if (response.status === 419) {
        window.location.href = window.APP_URL + '/login?session=expired';
        return true;
    }
    return false;
}

function syncVersionsData() {
    return {
        modalOpen: false,
        isEdit: false,
        editId: null,
        showDeleteModal: false,
        deleteItemId: null,
        deleteItemVersion: '',

        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.$nextTick(() => {
                document.getElementById('form-typeapp').value = 'mobile';
                document.getElementById('form-version').value = '';
                document.getElementById('form-version').classList.remove('border-red-500');
                document.getElementById('form-version').classList.add('border-gray-200');
                document.getElementById('form-status').value = 'active';
                document.getElementById('form-notes').value = '';
                document.querySelectorAll('.text-red-600').forEach(el => el.remove());
            });
            this.modalOpen = true;
        },

        async openEdit(id) {
            try {
                const response = await fetch(window.APP_URL + '/admin/sync-versions/' + id + '/edit-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                if (checkSessionExpired(response)) return;

                const result = await response.json();
                if (result.success) {
                    this.editId = id;
                    this.isEdit = true;
                    this.modalOpen = true;
                    this.$nextTick(() => {
                        document.getElementById('form-typeapp').value = result.data.typeapp || 'mobile';
                        document.getElementById('form-version').value = result.data.version;
                        document.getElementById('form-status').value = result.data.status;
                        document.getElementById('form-notes').value = result.data.notes || '';
                    });
                }
            } catch (e) {
                console.error('Error al cargar datos:', e);
                alert('Error al cargar los datos');
            }
        },

        async submitForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const url = this.isEdit
                ? window.APP_URL + '/admin/sync-versions/' + this.editId
                : window.APP_URL + '/admin/sync-versions';

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

                if (checkSessionExpired(response)) return;

                const result = await response.json();

                if (result.success) {
                    this.modalOpen = false;
                    window.location.reload();
                } else if (result.errors) {
                    for (const [field, messages] of Object.entries(result.errors)) {
                        const input = document.getElementById('form-' + field);
                        if (input) {
                            input.classList.remove('border-gray-200');
                            input.classList.add('border-red-500');
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

        confirmDelete(id, version) {
            this.deleteItemId = id;
            this.deleteItemVersion = version;
            this.showDeleteModal = true;
        },

        async executeDelete() {
            try {
                const response = await fetch(window.APP_URL + '/admin/sync-versions/' + this.deleteItemId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                if (checkSessionExpired(response)) return;

                const result = await response.json();

                if (result.success) {
                    this.showDeleteModal = false;
                    window.location.reload();
                } else {
                    alert(result.message || 'Error al eliminar');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Error al eliminar la versión');
            }
        },

        init() {
            // No se requiere inicialización especial
        }
    };
}
</script>
@endsection
