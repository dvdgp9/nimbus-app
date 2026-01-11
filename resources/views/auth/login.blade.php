<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Google Login Button -->
    <div class="mb-8">
        <a href="{{ route('google.login') }}" 
           class="w-full inline-flex items-center justify-center gap-3 px-4 py-3.5 bg-white rounded-xl font-bold text-slate-900 shadow-xl shadow-cyan-500/10 hover:bg-slate-50 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span class="text-base">Continuar con Google</span>
        </a>
    </div>

    <!-- Error de Google -->
    @if ($errors->has('google'))
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
            <p class="text-sm text-red-400 font-medium text-center">{{ $errors->first('google') }}</p>
        </div>
    @endif

    <!-- Divider -->
    <div class="relative mb-8">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-white/10"></div>
        </div>
        <div class="relative flex justify-center text-sm uppercase tracking-widest font-bold">
            <span class="px-4 bg-[#1a2436] text-slate-500">O usa tu email</span>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-300 mb-2">Correo Electrónico</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autofocus 
                   autocomplete="username" 
                   class="block w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition duration-200"
                   placeholder="tu@ejemplo.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-sm font-semibold text-slate-300">Contraseña</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-cyan-400 hover:text-cyan-300 transition duration-200" href="{{ route('password.request') }}">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <input id="password" 
                   type="password" 
                   name="password" 
                   required 
                   autocomplete="current-password" 
                   class="block w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition duration-200"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-white/10 bg-white/5 text-cyan-500 focus:ring-offset-slate-900 focus:ring-cyan-500" name="remember">
            <label for="remember_me" class="ml-2 text-sm font-medium text-slate-400">Mantener sesión iniciada</label>
        </div>

        <div>
            <button type="submit" 
                    class="w-full py-4 bg-cyan-400 hover:bg-cyan-300 text-slate-900 font-extrabold rounded-xl shadow-lg shadow-cyan-500/20 transform hover:scale-[1.01] active:scale-[0.99] transition-all duration-200">
                Iniciar Sesión
            </button>
        </div>
    </form>
</x-guest-layout>
