<x-app-layout>
<div class="min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full">
    {{-- Progress indicator --}}
    <div class="mb-8">
      <div class="flex items-center justify-center gap-2">
        @for($i = 1; $i <= 5; $i++)
          <div class="w-3 h-3 rounded-full bg-cyan-500"></div>
        @endfor
      </div>
      <p class="text-center text-white/40 text-sm mt-2">Paso 5 de 5</p>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/5 rounded-2xl border border-white/10 p-8 text-center">
      {{-- Success animation --}}
      <div class="mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full shadow-lg shadow-emerald-500/20 animate-pulse">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
      </div>

      <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
        ¡Todo listo! 🎉
      </h1>

      <p class="text-white/70 text-lg mb-8 max-w-md mx-auto">
        Tu cuenta de Nimbus está configurada y lista para gestionar tus citas automáticamente.
      </p>

      {{-- Summary --}}
      <div class="bg-white/5 rounded-xl p-6 mb-8 text-left">
        <h3 class="text-white font-semibold mb-4 text-center">Resumen de tu configuración</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-white/5 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-cyan-400">{{ $patientsCount }}</div>
            <div class="text-white/60 text-sm">Pacientes</div>
          </div>
          <div class="bg-white/5 rounded-lg p-4 text-center">
            @if($isConnected)
              <div class="text-3xl font-bold text-emerald-400">✓</div>
              <div class="text-white/60 text-sm">Calendario conectado</div>
            @else
              <div class="text-3xl font-bold text-amber-400">⏳</div>
              <div class="text-white/60 text-sm">Calendario pendiente</div>
            @endif
          </div>
        </div>
      </div>

      {{-- What happens next --}}
      <div class="bg-cyan-500/10 border border-cyan-500/20 rounded-xl p-6 mb-8 text-left">
        <h3 class="text-cyan-300 font-semibold mb-3">¿Qué pasa ahora?</h3>
        <ul class="space-y-2 text-white/70 text-sm">
          @if($isConnected)
            <li class="flex items-start gap-2">
              <span class="text-cyan-400">→</span>
              Nimbus sincronizará tus citas de las próximas 2 semanas
            </li>
            <li class="flex items-start gap-2">
              <span class="text-cyan-400">→</span>
              Reconocerá automáticamente a tus pacientes por el código en el título
            </li>
            <li class="flex items-start gap-2">
              <span class="text-cyan-400">→</span>
              Enviará recordatorios 48h antes de cada cita
            </li>
          @else
            <li class="flex items-start gap-2">
              <span class="text-amber-400">⚠️</span>
              Recuerda conectar tu calendario para activar la sincronización automática
            </li>
          @endif
        </ul>
      </div>

      {{-- CTA --}}
      <form action="{{ route('onboarding.complete') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg w-full md:w-auto px-12">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
          Empezar a usar Nimbus
        </button>
      </form>

      {{-- Back option --}}
      <div class="mt-6">
        <form action="{{ route('onboarding.previous') }}" method="POST" class="inline">
          @csrf
          <button type="submit" class="text-white/40 hover:text-white/60 text-sm transition">
            ← Volver y revisar configuración
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
