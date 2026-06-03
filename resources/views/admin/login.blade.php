@extends('layouts.app')

@section('title', 'Iniciar Sesión - Chrystal Mobile')

@push('scripts')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush

@section('content')
<div class="min-h-screen flex">
    {{-- Branding Side --}}
    <div class="hidden lg:flex lg:w-5/12 bg-gray-900 flex-col justify-between p-12 relative overflow-hidden">
        {{-- Pattern Overlay --}}
        <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="text-white font-semibold text-lg">Chrystal Mobile</span>
            </div>
            <p class="text-gray-400 text-sm">Sistema de gestión empresarial</p>
        </div>

        <div class="relative z-10">
            <h2 class="text-white text-2xl font-medium mb-4">Administra empresas, vendedores y sincronización móvil</h2>
            <ul class="space-y-3 text-gray-300 text-sm">
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Gestión de empresas con API keys
                </li>
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Control de acceso de vendedores
                </li>
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Sincronización en tiempo real
                </li>
            </ul>
        </div>

        <div class="relative z-10 text-gray-500 text-xs">
            &copy; 2026 Chrystal Mobile
        </div>
    </div>

    {{-- Form Side --}}
    <div class="flex-1 flex items-center justify-center px-6 bg-gray-50">
        <div class="w-full max-w-sm">
            {{-- Mobile Header --}}
            <div class="lg:hidden mb-8 text-center">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-lg text-gray-900">Chrystal Mobile</span>
                </div>
                <p class="text-gray-500 text-sm">Panel de administración</p>
            </div>

            {{-- Form Card --}}
            <div class="bg-white border border-gray-200 rounded-xl p-8">
                <h1 class="text-xl font-semibold text-gray-900 mb-1">Iniciar sesión</h1>
                <p class="text-sm text-gray-500 mb-8">Ingresa tus credenciales para continuar</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-5">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 focus:bg-white {{ $errors->has('email') ? 'border-red-300 focus:border-red-500' : '' }}"
                               placeholder="ejemplo@empresa.com">
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                        </div>
                        <input type="password" name="password" id="password" required
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 outline-none transition-all focus:border-gray-900 focus:bg-white {{ $errors->has('email') ? 'border-red-300 focus:border-red-500' : '' }}"
                               placeholder="••••••••">
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Captcha Error --}}
                    @error('captcha')
                        <div class="mb-5 p-3 bg-red-50 border border-red-100 rounded-lg">
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        </div>
                    @enderror

                    {{-- Google reCAPTCHA --}}
                    <div class="mb-6">
                        <div class="g-recaptcha" data-sitekey="{{ env('GOOGLE_RECAPTCHA_SITE_KEY') }}"></div>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-900/20">
                        Iniciar sesión
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <p class="text-center text-xs text-gray-400 mt-6">
                Sistema protegido con reCAPTCHA
            </p>
        </div>
    </div>
</div>
@endsection