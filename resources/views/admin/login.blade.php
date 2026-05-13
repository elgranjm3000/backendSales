@extends('layouts.app')

@section('title', 'Iniciar Sesión - Chrystal Mobile')

@push('scripts')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-lg mx-auto mb-3">C</div>
            <h1 class="text-xl font-bold text-gray-900">Chrystal Mobile</h1>
            <p class="text-sm text-gray-500 mt-1">Panel de administración</p>
        </div>

        {{-- Card --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors @error('email') border-red-300 @enderror"
                           placeholder="cajero@ejemplo.com">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors @error('email') border-red-300 @enderror"
                           placeholder="•••••">
                </div>

                @error('email')
                    <div class="mb-4 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                        {{ $message }}
                    </div>
                @enderror

                @error('captcha')
                    <div class="mb-4 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                        {{ $message }}
                    </div>
                @enderror

                {{-- Google reCAPTCHA --}}
                <div class="mb-6">
                    <div class="g-recaptcha" data-sitekey="{{ env('GOOGLE_RECAPTCHA_SITE_KEY') }}"></div>
                    @error('captcha')
                        <span class="text-red-500 text-sm">Requerido</span>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>
@endsection
