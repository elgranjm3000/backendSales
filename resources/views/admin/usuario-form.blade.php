@extends('layouts.admin')

  @section('title', (isset($user) ? 'Editar' : 'Nuevo') . ' Usuario - Chrystal Mobile')

  @section('content')
  <div class="p-4 md:p-6 max-w-2xl">
      <div class="mb-6">
          <a href="{{ route('admin.usuarios') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
              </svg>
              Volver a usuarios
          </a>
      </div>

      <div class="bg-white border border-gray-200 rounded-lg p-6">
          <h1 class="text-lg font-semibold text-gray-900 mb-6">{{ isset($user) ? 'Editar Usuario' : 'Nuevo Usuario' }}</h1>

          <form method="POST"
                action="{{ isset($user) ? route('admin.usuarios.update', $user->id) : route('admin.usuarios.store') }}">
              @csrf
              @if(isset($user))
                  @method('PUT')
              @endif

              <div class="space-y-4">
                  {{-- Nombre --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                      <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                             class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 outline-none
  @error('name') border-red-300 @enderror">
                      @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                  </div>

                  {{-- Email --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                      <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                             class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 outline-none
  @error('email') border-red-300 @enderror">
                      @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                  </div>

                  {{-- Teléfono --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                      <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                             class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 outline-none">
                  </div>

                  {{-- Password --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">
                          Contraseña{{ isset($user) ? ' (dejar vacío para no cambiar)' : '' }}
                      </label>
                      <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                             class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 outline-none
  @error('password') border-red-300 @enderror">
                      @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                  </div>

                  {{-- Rol --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                      <select name="role" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 outline-none
  bg-white">
                          @foreach($roles as $role)
                              <option value="{{ $role }}" @selected(old('role', $user->role->value ?? '') === $role)>
                                  @switch($role)
                                  @case('admin') Admin @break
                                  @case('manager') Manager @break
                                  @case('cajero') Sincronizador @break
                                  @default {{ $role }}
                               @endswitch
                              </option>
                          @endforeach
                      </select>
                  </div>

                  {{-- Status --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                      <select name="status" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 outline-none
  bg-white">
                          <option value="active" @selected(old('status', $user->status->value ?? '') === 'active')>Activo</option>
                          <option value="inactive" @selected(old('status', $user->status->value ?? '') === 'inactive')>Inactivo</option>
                      </select>
                  </div>
              </div>

              <div class="mt-6 flex items-center gap-3">
                  <button type="submit"
                          class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                      {{ isset($user) ? 'Actualizar' : 'Crear Usuario' }}
                  </button>
                  <a href="{{ route('admin.usuarios') }}"
                     class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                      Cancelar
                  </a>
              </div>
          </form>
      </div>
  </div>
  @endsection
