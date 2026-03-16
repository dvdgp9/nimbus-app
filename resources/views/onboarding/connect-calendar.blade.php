<x-app-layout>
<div class="min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full">
    {{-- Progress indicator --}}
    <div class="mb-8">
      <div class="flex items-center justify-center gap-2">
        @for($i = 1; $i <= 5; $i++)
          <div class="w-3 h-3 rounded-full {{ $i <= 3 ? 'bg-cyan-500' : 'bg-white/20' }}"></div>
        @endfor
      </div>
      <p class="text-center text-white/40 text-sm mt-2">Paso 3 de 5</p>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/5 rounded-2xl border border-white/10 p-8 text-center">
      <div class="mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Conecta tu calendario</h1>
        <p class="text-white/60">Nimbus sincronizará tus citas automáticamente desde Google Calendar</p>
      </div>

      @if($hasConfiguredCalendars)
        {{-- Calendars configured --}}
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-6 mb-8">
          <div class="flex items-center justify-center gap-3 mb-3">
            <div class="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
            <span class="text-emerald-300 font-semibold text-lg">¡Calendarios listos!</span>
          </div>
          <p class="text-emerald-300/70 text-sm">Tu cuenta de Google está conectada y ya has seleccionado qué calendarios usar</p>
        </div>

        <div class="bg-white/5 rounded-xl p-6 text-left mb-8">
          <h3 class="text-white font-semibold mb-3">¿Qué pasa ahora?</h3>
          <ul class="space-y-2 text-white/70 text-sm">
            <li class="flex items-start gap-2">
              <span class="text-cyan-400">•</span>
              Nimbus leerá tus eventos de las próximas 2 semanas
            </li>
            <li class="flex items-start gap-2">
              <span class="text-cyan-400">•</span>
              Buscará los códigos de paciente en el título de cada cita
            </li>
            <li class="flex items-start gap-2">
              <span class="text-cyan-400">•</span>
              Detectará automáticamente los códigos presentes en tus citas
            </li>
          </ul>
        </div>
      @elseif($hasGoogleAccount)
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-6 mb-8">
          <div class="flex items-center justify-center gap-3 mb-3">
            <div class="w-10 h-10 bg-amber-500/20 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <span class="text-amber-300 font-semibold text-lg">Cuenta conectada, calendarios pendientes</span>
          </div>
          <p class="text-amber-300/70 text-sm">
            Tu cuenta <strong class="text-amber-200">{{ $connectedAccountEmail }}</strong> ya está conectada, pero aún no has elegido qué calendarios usar.
          </p>
        </div>

        <div class="bg-white/5 rounded-xl p-6 mb-8 text-left">
          <h3 class="text-white font-semibold mb-3">Siguiente acción</h3>
          <p class="text-white/70 text-sm mb-4">
            Selecciona los calendarios que Nimbus debe leer. La sincronización inicial solo servirá para detectar códigos y preparar plantillas, <strong class="text-white">sin activar envíos automáticos todavía</strong>.
          </p>
          <a href="{{ route('calendars.index', ['account' => $connectedAccountEmail]) }}" class="btn btn-primary inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Seleccionar calendarios
          </a>
        </div>
      @else
        {{-- No Google account connected yet --}}
        <div class="bg-white/5 rounded-xl p-6 mb-8 text-left">
          <h3 class="text-white font-semibold mb-3">¿Qué permisos necesita Nimbus?</h3>
          <ul class="space-y-2 text-white/70 text-sm">
            <li class="flex items-start gap-2">
              <svg class="w-4 h-4 text-cyan-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span><strong class="text-white">Ver eventos</strong> — Para leer tus citas programadas</span>
            </li>
            <li class="flex items-start gap-2">
              <svg class="w-4 h-4 text-cyan-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span><strong class="text-white">Editar eventos</strong> — Para marcar citas confirmadas con "OK"</span>
            </li>
          </ul>
        </div>

        <a href="{{ route('google.redirect') }}" class="btn btn-primary btn-lg inline-flex items-center gap-3">
          <svg class="w-6 h-6" viewBox="0 0 24 24">
            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Conectar con Google Calendar
        </a>

        <p class="text-white/40 text-sm mt-4">
          Serás redirigido a Google para autorizar la conexión
        </p>
      @endif

      {{-- Navigation --}}
      <div class="flex justify-between items-center mt-8 pt-6 border-t border-white/10">
        <form action="{{ route('onboarding.previous') }}" method="POST">
          @csrf
          <button type="submit" class="btn bg-white/5 hover:bg-white/10 text-white/60">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
            </svg>
            Anterior
          </button>
        </form>

        <form action="{{ route('onboarding.next') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-primary" {{ !$hasConfiguredCalendars ? 'disabled' : '' }}>
            Siguiente
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </button>
        </form>
      </div>

      @if(!$hasConfiguredCalendars)
        <div class="mt-4">
          <form action="{{ route('onboarding.next') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="text-white/40 hover:text-white/60 text-sm transition">
              Saltar este paso (conectar después)
            </button>
          </form>
        </div>
      @endif
    </div>
  </div>
</div>
</x-app-layout>
