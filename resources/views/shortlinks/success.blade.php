<x-app-layout>

  <div class="page-container flex items-center justify-center min-h-[60vh]">
    <div class="max-w-xl w-full">
      <div class="event-card border-2 border-white/10">
        <div class="flex items-start gap-4 mb-4">
          @php
            $isConfirmed = str_contains($title, 'confirmada');
            $isCancelled = str_contains($title, 'cancelada');
          @endphp

          <div class="shrink-0 rounded-full bg-emerald-500/10 border border-emerald-400/40 p-3 {{ $isCancelled ? 'bg-red-500/10 border-red-400/40' : '' }}">
            @if($isConfirmed)
              {{-- Icono estilo iconoir: check circle --}}
              <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" stroke-width="1.7" />
                <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M9 12.5l2 2.5 4-5" />
              </svg>
            @elseif($isCancelled)
              {{-- Icono estilo iconoir: cancel --}}
              <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" stroke-width="1.7" />
                <path stroke-width="1.7" stroke-linecap="round" d="M9 9l6 6M15 9l-6 6" />
              </svg>
            @else
              {{-- Icono info --}}
              <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" stroke-width="1.7" />
                <path stroke-width="1.7" stroke-linecap="round" d="M12 8v.01M12 11v5" />
              </svg>
            @endif
          </div>

          <div class="flex-1">
            <h1 class="text-2xl md:text-3xl font-semibold text-white mb-2">{{ $title }}</h1>

            <p class="text-white/70 mb-4">
              @if($isConfirmed)
                Nos vemos en la cita. Gracias por confirmar tu asistencia.
              @elseif($isCancelled)
                Tu cita ha sido cancelada. Espero que nos veamos pronto.
              @else
                {{ $message }}
              @endif
            </p>

            @if(isset($appointment))
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-white/80">
                <div>
                  <div class="text-xs uppercase tracking-wide text-white/40 mb-1">Título</div>
                  <div>{{ $appointment->summary }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-white/40 mb-1">Fecha</div>
                  <div>{{ $appointment->formatted_date }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-white/40 mb-1">Hora</div>
                  <div>{{ $appointment->formatted_time }} {{ $appointment->timezone ? "({$appointment->timezone})" : '' }}</div>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
