<x-guest-layout>
    <!-- Header -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-white mb-2">Crear cuenta</h2>
        <p class="text-cyan-100 text-sm">Únete a Nimbus hoy</p>
    </div>

    <!-- Google Register Button -->
    <div class="mb-6">
        <a href="{{ route('google.login') }}" 
           class="w-full inline-flex items-center justify-center gap-3 px-4 py-3.5 bg-white border border-gray-200 rounded-xl font-semibold text-gray-700 shadow-md hover:shadow-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span>Registrarse con Google</span>
        </a>
    </div>

    <!-- Error de Google -->
    @if ($errors->has('google'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ $errors->first('google') }}</p>
        </div>
    @endif

    <!-- Error de Google -->
    @if ($errors->has('google'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ $errors->first('google') }}</p>
        </div>
    @endif

    <!-- Divider -->
    <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-white/20"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-3 glass-subtle text-white font-medium">O regístrate con email</span>
        </div>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-white mb-2">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" 
                   class="block w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 focus:ring-offset-transparent transition-all" 
                   placeholder="Tu nombre">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <label for="email" class="block text-sm font-medium text-white mb-2">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" 
                   class="block w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 focus:ring-offset-transparent transition-all" 
                   placeholder="tu@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block text-sm font-medium text-white mb-2">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" 
                   class="block w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 focus:ring-offset-transparent transition-all" 
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <label for="password_confirmation" class="block text-sm font-medium text-white mb-2">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" 
                   class="block w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 focus:ring-offset-transparent transition-all" 
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200">
                Crear cuenta
            </button>
        </div>

        <div class="mt-4 text-center">
            <a class="text-sm text-cyan-300 hover:text-cyan-200 hover:underline transition" href="{{ route('login') }}">
                ¿Ya tienes cuenta? Inicia sesión
            </a>
        </div>
    </form>
</x-guest-layout>
