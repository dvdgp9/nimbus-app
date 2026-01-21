<x-app-layout>

  <div class="page-container flex items-center justify-center min-h-[60vh]">
    <div class="max-w-xl w-full">
      <div class="event-card border-2 border-red-500/40">
        <div class="flex items-start gap-4">
          <div class="shrink-0 rounded-full bg-red-500/10 border border-red-400/50 p-3">
            {{-- Icono estilo advertencia --}}
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v5" />
              <circle cx="12" cy="16" r="0.9" fill="currentColor" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 3 3.5 19h17z" />
            </svg>
          </div>

          <div class="flex-1">
            <h1 class="text-2xl md:text-3xl font-semibold text-white mb-2">{{ $message }}</h1>
            <p class="text-white/70 mb-4">{{ $detail }}</p>

            <p class="text-sm text-white/60 mt-4">
              ¿Necesitas ayuda? Contacta con tu profesional para revisar tu cita.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
