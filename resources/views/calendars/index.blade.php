<x-app-layout>


<div class="page-container max-w-4xl">
  {{-- Page Header --}}
  <div class="page-header mb-8">
    <h1>Configuración</h1>
    <p>Gestiona tu cuenta, calendarios y preferencias</p>
  </div>

  {{-- Tabs --}}
  <div class="flex gap-2 mb-6">
    <a href="{{ route('profile.edit') }}" class="px-4 py-2 rounded-lg font-medium transition bg-white/5 text-white/60 hover:bg-white/10 hover:text-white/80">
      <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
      </svg>
      Mi cuenta
    </a>
    <a href="{{ route('calendars.index') }}" class="px-4 py-2 rounded-lg font-medium transition bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
      <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
      Calendarios
    </a>
  </div>

  {{-- Connected Account Info --}}
  <div class="mb-6 flex items-center gap-3 text-sm text-white/60">
    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <span>Cuenta conectada: <span class="text-white font-medium">{{ $account }}</span></span>
  </div>

  <div class="bg-white/5 rounded-xl border border-white/10 p-6">
    <form method="POST" action="{{ route('calendars.store') }}" class="space-y-6">
      @csrf
      <input type="hidden" name="account" value="{{ $account }}">

      <div class="space-y-3">
        @forelse ($calendars as $cal)
          <label class="flex items-start gap-4 p-4 rounded-lg bg-white/5 hover:bg-white/10 transition-all cursor-pointer">
            <input 
              type="checkbox" 
              name="calendars[]" 
              value="{{ $cal['id'] }}" 
              class="form-checkbox mt-0.5"
              {{ (isset($enabled[$cal['id']]) && $enabled[$cal['id']]) || $cal['primary'] ? 'checked' : '' }}>
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <div class="text-base font-semibold text-white transition">
                  {{ $cal['summary'] ?? $cal['id'] }}
                </div>
                @if($cal['primary'])
                  <span class="badge badge-live text-xs">
                    Principal
                  </span>
                @endif
              </div>
              <div class="text-xs text-white/50 mt-1 font-mono truncate">
                {{ $cal['id'] }}
              </div>
            </div>
            <svg class="w-5 h-5 text-white/30 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </label>
        @empty
          <div class="empty-state">
            <div class="icon">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3>No se encontraron calendarios</h3>
            <p>Verifica que la cuenta esté correctamente conectada</p>
          </div>
        @endforelse
      </div>

      <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-white/10">
        <button type="submit" class="btn btn-primary flex-1 sm:flex-initial justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          Guardar selección
        </button>
        <a class="btn btn-dark flex-1 sm:flex-initial justify-center" href="{{ route('events.index', ['account' => $account]) }}">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Ver eventos
        </a>
      </div>
    </form>
  </div>

  {{-- Info card --}}
  <div class="bg-white/5 rounded-xl border border-white/10 p-6 mt-6">
    <div class="flex items-start gap-3">
      <svg class="w-5 h-5 text-[var(--nimbus-accent)] mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <div class="text-sm text-white/70">
        <p class="font-medium text-white/90 mb-1">Selección de calendarios</p>
        <p>Solo los calendarios seleccionados serán sincronizados y tendrán recordatorios automáticos. Los calendarios marcados como "Principal" se seleccionan automáticamente.</p>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
