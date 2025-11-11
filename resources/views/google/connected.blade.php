@extends('layouts.app')

@section('title', 'Cuenta conectada')

@section('content')
<div class="page-container max-w-2xl">
  {{-- Success State --}}
  <div class="glass rounded-xl p-8 text-center">
    {{-- Success Icon --}}
    <div class="flex justify-center mb-6">
      <div class="relative">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500 to-[var(--nimbus-accent)] rounded-full blur-xl opacity-60 animate-pulse"></div>
        <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-emerald-500 to-[var(--nimbus-accent)] flex items-center justify-center">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
      </div>
    </div>

    {{-- Success Message --}}
    <h1 class="text-3xl font-bold mb-3" style="background: linear-gradient(135deg, #10b981, var(--nimbus-accent)); -webkit-background-clip: text; background-clip: text; color: transparent;">
      ¡Conexión exitosa!
    </h1>
    <p class="text-white/80 text-lg mb-2">
      Has conectado correctamente tu cuenta de Google Calendar
    </p>
    <p class="text-white/60 text-sm">
      <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
      </svg>
      <strong class="text-white/90">{{ $email }}</strong>
    </p>

    {{-- Action Buttons --}}
    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
      <a href="{{ route('calendars.index', ['account' => $email]) }}" class="btn btn-primary justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        Seleccionar calendarios
      </a>
      <a href="{{ route('events.index', ['account' => $email]) }}" class="btn btn-dark justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Ver próximos eventos
      </a>
    </div>

    <div class="mt-6">
      <a href="/" class="text-sm text-white/60 hover:text-white/80 transition">
        ← Volver al panel
      </a>
    </div>
  </div>

  {{-- Next Steps --}}
  <div class="glass rounded-xl p-6 mt-6">
    <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-[var(--nimbus-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
      </svg>
      Próximos pasos
    </h2>
    <ol class="space-y-3 text-sm text-white/70">
      <li class="flex items-start gap-3">
        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] flex items-center justify-center text-white text-xs font-bold">1</span>
        <div>
          <strong class="text-white/90">Selecciona tus calendarios</strong>
          <p class="text-xs mt-0.5">Elige qué calendarios deseas sincronizar con Nimbus</p>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] flex items-center justify-center text-white text-xs font-bold">2</span>
        <div>
          <strong class="text-white/90">Revisa tus eventos</strong>
          <p class="text-xs mt-0.5">Consulta las próximas citas programadas en las siguientes 48 horas</p>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] flex items-center justify-center text-white text-xs font-bold">3</span>
        <div>
          <strong class="text-white/90">Configura recordatorios</strong>
          <p class="text-xs mt-0.5">Los recordatorios automáticos se enviarán según tu configuración</p>
        </div>
      </li>
    </ol>
  </div>
</div>
@endsection
