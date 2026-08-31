<x-app-layout>

  <div class="page-container flex items-center justify-center min-h-[60vh]">
    <div class="max-w-xl w-full">
      <div class="event-card border-2 border-white/10">
        <div class="flex items-start gap-4">
          <div class="shrink-0 rounded-full bg-cyan-500/10 border border-cyan-400/40 p-3">
            <svg class="w-6 h-6 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="9" stroke-width="1.7" />
              <path stroke-width="1.7" stroke-linecap="round" d="M12 8v5M12 16v.01" />
            </svg>
          </div>

          <div class="flex-1">
            <h1 class="text-2xl md:text-3xl font-semibold text-white mb-2">{{ $title }}</h1>
            <p class="text-white/70 mb-4">{{ $message }}</p>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-white/80">
              <div>
                <div class="text-xs uppercase tracking-wide text-white/40 mb-1">Fecha</div>
                <div>{{ $appointment->formatted_date }}</div>
              </div>
              <div>
                <div class="text-xs uppercase tracking-wide text-white/40 mb-1">Hora</div>
                <div>{{ $appointment->formatted_time }} {{ $appointment->timezone ? "({$appointment->timezone})" : '' }}</div>
              </div>
            </div>

            <form method="POST" action="{{ route('shortlink.execute', $token) }}" class="mt-6">
              @csrf
              <button type="submit" class="shortlink-action-button {{ $buttonClass }}">
                {{ $button }}
              </button>
            </form>

            @if($cancelUrl || $rescheduleUrl)
              <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($cancelUrl)
                  <a href="{{ $cancelUrl }}" class="shortlink-action-button shortlink-action-cancel">
                    Cancelar cita
                  </a>
                @endif
                @if($rescheduleUrl)
                  <a href="{{ $rescheduleUrl }}" class="shortlink-action-button shortlink-action-reschedule">
                    Cambiar cita
                  </a>
                @endif
              </div>
            @endif

            <p class="text-xs text-white/45 mt-4">
              La cita no cambiará hasta que pulses el botón.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
