<x-app-layout>
<div class="page-container max-w-2xl">
  {{-- Info Alert if needed --}}
  @if (session('status'))
    <div class="alert alert-error mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
      </svg>
      {{ session('status') }}
    </div>
  @endif

  {{-- Main Card --}}
  <div class="glass rounded-xl p-8 text-center">
    {{-- Icon --}}
    <div class="flex justify-center mb-6">
      <div class="relative">
        <div class="absolute inset-0 bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] rounded-full blur-xl opacity-60"></div>
        <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] flex items-center justify-center">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
      </div>
    </div>

    {{-- Title --}}
    <h1 class="text-3xl font-bold mb-3 text-white">
      Conectar Google Calendar
    </h1>
    <p class="text-white/80 text-lg mb-2">
      Conecta tu cuenta de Google para sincronizar tus calendarios
    </p>
    <p class="text-white/60 text-sm mb-8">
      Nimbus necesita acceso de solo lectura a tus calendarios para poder enviar recordatorios automáticos
    </p>

    {{-- CTA Button --}}
    <form action="{{ route('google.redirect') }}" method="GET">
      <button type="submit" class="btn btn-primary justify-center text-lg px-8 py-3">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        <span>Conectar con Google</span>
      </button>
    </form>

    <p class="text-xs text-white/50 mt-6">
      Al conectar, serás redirigido a Google para autorizar el acceso
    </p>
  </div>

  {{-- Info Card --}}
  <div class="glass rounded-xl p-6 mt-6">
    <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-[var(--nimbus-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
      </svg>
      ¿Qué permisos necesita Nimbus?
    </h2>
    <ul class="space-y-3 text-sm text-white/70">
      <li class="flex items-start gap-3">
        <svg class="w-5 h-5 text-[var(--nimbus-accent)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
          <strong class="text-white/90">Ver tus calendarios</strong>
          <p class="text-xs mt-0.5">Para listar los calendarios disponibles y que puedas elegir cuáles sincronizar</p>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <svg class="w-5 h-5 text-[var(--nimbus-accent)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
          <strong class="text-white/90">Leer eventos próximos</strong>
          <p class="text-xs mt-0.5">Para consultar las próximas citas y enviar recordatorios automáticos</p>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <svg class="w-5 h-5 text-[var(--nimbus-accent)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
          <strong class="text-white/90">Acceso permanente (offline)</strong>
          <p class="text-xs mt-0.5">Para poder enviar recordatorios incluso cuando no estés usando la aplicación</p>
        </div>
      </li>
    </ul>
    
    <div class="mt-4 p-4 bg-white/5 rounded-lg border border-white/10">
      <p class="text-xs text-white/60">
        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
        <strong>Tu privacidad es importante:</strong> Nimbus solo accede a la información necesaria para funcionar. No compartimos tus datos con terceros ni los usamos para otros fines.
      </p>
    </div>
  </div>
</div>
</x-app-layout>
