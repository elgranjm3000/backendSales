@extends('layouts.admin')

@section('title', 'Usuarios - Chrystal Mobile')

@section('content')
<div x-data="usuariosData()" x-init="init()" class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Usuarios</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestión de usuarios del sistema</p>
        </div>
        <button @click="openCreate()" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo usuario
        </button>
    </div>

    {{-- Filtros --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('admin.usuarios') }}" id="filterForm">
            <div class="flex flex-col lg:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="flex-1 lg:flex-none lg:w-64 px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                       placeholder="Buscar por nombre o email...">
                <div class="flex flex-col sm:flex-row gap-3">
                    <select name="role" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-600 outline-none transition-all focus:border-gray-900 min-w-[140px]">
                        <option value="">Todos los roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="cajero" {{ request('role') === 'cajero' ? 'selected' : '' }}>Cajero</option>
                    </select>
                    <select name="status" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-600 outline-none transition-all focus:border-gray-900 min-w-[140px]">
                        <option value="">Todos los estados</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
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
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Nombre</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Teléfono</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Rol</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($usuarios as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->phone ?? '-' }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">
                                @switch($user->role->value)
                                    @case('admin') Admin @break
                                    @case('manager') Manager @break
                                    @case('cajero') Sincronizador @break
                                    @default {{ $user->role->value }}
                                @endswitch
                            </td>
                            <td class="px-4 py-3">
                                @if($user->status->value === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEdit({{ $user->id }})" :disabled="loading && editId === {{ $user->id }}" class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 p-2 rounded-lg transition-all disabled:opacity-50" title="Editar">
                                        <svg x-show="!loading || editId !== {{ $user->id }}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <svg x-show="loading && editId === {{ $user->id }}" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $user->id) }}" onsubmit="return confirm('¿Eliminar a {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg transition-all text-red-600 hover:text-red-700 hover:bg-red-100" title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <p class="text-gray-500">No hay usuarios registrados</p>
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
        {{ $usuarios->links() }}
    </div>

    {{-- Modal Crear/Editar --}}
    <div @keydown.escape.window="modalOpen = false" x-cloak>
        <div x-show="modalOpen" x-transition.opacity.duration.200ms class="fixed inset-0 bg-gray-900/40 z-40" @click="modalOpen = false"></div>
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-gray-200 rounded-lg w-full max-w-md shadow-sm" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900" x-text="isEdit ? 'Editar usuario' : 'Nuevo usuario'"></h2>
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
                            <label for="form-name" class="block text-sm font-medium text-gray-700 mb-1.5">Nombre *</label>
                            <input type="text" id="form-name" name="name" required
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                   placeholder="Nombre completo">
                        </div>

                        <div>
                            <label for="form-email" class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                            <input type="email" id="form-email" name="email" required
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                   placeholder="email@ejemplo.com">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="form-phone" class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
                                <input type="text" id="form-phone" name="phone"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       placeholder="0414-1234567">
                            </div>
                            <div>
                                <label for="form-role" class="block text-sm font-medium text-gray-700 mb-1.5">Rol *</label>
                                <select id="form-role" name="role" required
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-900 outline-none transition-all focus:border-gray-900">
                                    <option value="">Seleccionar</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="cajero">Cajero</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="form-password" class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña <span x-show="!isEdit" class="text-red-500">*</span></label>
                                <input type="password" id="form-password" name="password" :required="!isEdit"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 bg-white"
                                       x-bind:placeholder="isEdit ? 'Dejar en blanco para mantener' : 'Contraseña'">
                            </div>
                            <div>
                                <label for="form-status" class="block text-sm font-medium text-gray-700 mb-1.5">Estado *</label>
                                <select id="form-status" name="status" required
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-900 outline-none transition-all focus:border-gray-900">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
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
</div>

<script>
window.APP_URL = "{{ url('/') }}";

function usuariosData() {
    return {
        modalOpen: false,
        isEdit: false,
        editId: null,
        editData: {},
        loading: false,
        async openEdit(id) {
            this.loading = true;
            this.editId = id;
            try {
                const response = await fetch(window.APP_URL + '/admin/usuarios/' + id + '/edit-data', {
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
                        document.getElementById('form-name').value = result.data.name || '';
                        document.getElementById('form-email').value = result.data.email || '';
                        document.getElementById('form-phone').value = result.data.phone || '';
                        document.getElementById('form-role').value = result.data.role || '';
                        document.getElementById('form-status').value = result.data.status || '';
                        document.getElementById('form-password').value = '';
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
                const fields = ['form-name', 'form-email', 'form-phone', 'form-role', 'form-password', 'form-status'];
                fields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.value = id === 'form-status' ? 'active' : '';
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
            const url = this.isEdit ? window.APP_URL + '/admin/usuarios/' + this.editId : window.APP_URL + '/admin/usuarios';

            const fieldMap = {
                'name': 'form-name',
                'email': 'form-email',
                'phone': 'form-phone',
                'role': 'form-role',
                'password': 'form-password',
                'status': 'form-status'
            };

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

                if (result.success || response.redirected) {
                    this.modalOpen = false;
                    window.location.reload();
                } else if (result.errors) {
                    for (const [field, messages] of Object.entries(result.errors)) {
                        const inputId = fieldMap[field] || 'form-' + field;
                        const input = document.getElementById(inputId);
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
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                this.openEdit(editId);
            }
        }
    };
}
</script>
@endsection
